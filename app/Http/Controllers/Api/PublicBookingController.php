<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\CreateAppointmentRequest;
use App\Http\Requests\Public\ListProvidersRequest;
use App\Http\Requests\Public\ReserveSlotRequest;
use App\Http\Requests\Public\TriageSlotsRequest;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Provider;
use App\Models\SlotReservation;
use App\Services\ClinicDateTimeService;
use App\Services\AppointmentCommunicationService;
use App\Services\AppointmentPaymentService;
use App\Services\PatientMatchingService;
use App\Services\ProviderAssignmentService;
use App\Services\SlotAvailabilityService;
use App\Services\InsuranceVerificationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class PublicBookingController extends Controller
{
    public function __construct(
        private readonly ProviderAssignmentService $providerAssignmentService,
        private readonly SlotAvailabilityService $slotAvailabilityService,
        private readonly ClinicDateTimeService $clinicDateTimeService,
        private readonly PatientMatchingService $patientMatchingService,
        private readonly AppointmentCommunicationService $appointmentCommunicationService,
        private readonly AppointmentPaymentService $appointmentPaymentService,
        private readonly InsuranceVerificationService $insuranceVerificationService,
    ) {
    }

    public function providers(Clinic $clinic, ListProvidersRequest $request): JsonResponse
    {
        $appointmentTypeId = (int) $request->integer('appointment_type_id');
        $isNewPatient = filter_var($request->input('is_new_patient', true), FILTER_VALIDATE_BOOL);

        $providers = Provider::query()
            ->where('clinic_id', $clinic->id)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get()
            ->filter(function (Provider $provider) use ($appointmentTypeId, $isNewPatient): bool {
                $types = $provider->default_appointment_types ?? [];

                if (! in_array($appointmentTypeId, $types, true)) {
                    return false;
                }

                if ($isNewPatient && ! $provider->is_accepting_new_patients) {
                    return false;
                }

                return true;
            })
            ->values();

        return response()->json($providers);
    }

    public function triage(Clinic $clinic, TriageSlotsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $provider = Provider::query()->findOrFail($validated['provider_id']);
        $appointmentType = AppointmentType::query()->findOrFail($validated['appointment_type_id']);

        if ($provider->clinic_id !== $clinic->id || $appointmentType->clinic_id !== $clinic->id) {
            return response()->json(['message' => 'Provider/appointment type must belong to the target clinic.'], 422);
        }

        if (! $this->providerSupportsAppointmentType($provider, $appointmentType->id)) {
            return response()->json(['message' => 'Provider is not mapped to appointment type.'], 422);
        }

        $forDateUtc = CarbonImmutable::parse($validated['date'].' 00:00:00', $clinic->timezone)->utc();

        $slots = $this->slotAvailabilityService->availableSlots($clinic, $provider, $appointmentType, $forDateUtc);

        return response()->json([
            'clinicTimezone' => $clinic->timezone,
            'minBookingNoticeHours' => $clinic->min_booking_notice_hours,
            'slots' => $slots,
        ]);
    }

    public function reserve(ReserveSlotRequest $request, Clinic $clinic): JsonResponse
    {
        $validated = $request->validated();
        $appointmentType = AppointmentType::query()->findOrFail($validated['appointment_type_id']);

        if ($appointmentType->clinic_id !== $clinic->id) {
            return response()->json(['message' => 'Appointment type must belong to clinic.'], 422);
        }

        try {
            $slotUtc = $this->clinicDateTimeService->parseClinicLocalToUtc($clinic, $validated['slot_local_datetime']);
        } catch (InvalidArgumentException) {
            return response()->json([
                'message' => 'Invalid local datetime for clinic timezone (possible DST gap).',
            ], 422);
        }

        if ($slotUtc->lessThan(now()->addHours($clinic->min_booking_notice_hours)->utc())) {
            return response()->json([
                'message' => 'Slot violates minimum booking notice.',
            ], 422);
        }

        $provider = $validated['provider_id'] === 'any'
            ? $this->providerAssignmentService->resolveProviderForAnyAvailable($clinic->id, $appointmentType->id, $slotUtc)
            : Provider::query()->findOrFail((int) $validated['provider_id']);

        if (! $provider || $provider->clinic_id !== $clinic->id) {
            return response()->json(['message' => 'Unable to resolve provider for reservation.'], 422);
        }

        if (! $this->providerSupportsAppointmentType($provider, $appointmentType->id)) {
            return response()->json(['message' => 'Provider is not mapped to appointment type.'], 422);
        }

        if (! $this->slotAvailabilityService->isSlotAvailable($clinic, $provider, $appointmentType, $slotUtc)) {
            return response()->json(['code' => 'SLOT_RESERVED', 'message' => 'Slot is no longer available.'], 409);
        }

        $reservation = $this->slotAvailabilityService->reserveSlot($clinic, $provider, $appointmentType, $slotUtc);

        return response()->json([
            'sessionToken' => $reservation->session_token,
            'expiresAt' => $reservation->expires_at,
            'providerAssigned' => $provider,
            'slotUtc' => $slotUtc->toIso8601String(),
            'slotLocal' => $slotUtc->setTimezone($clinic->timezone)->format('Y-m-d H:i:s'),
        ], 201);
    }

    public function createAppointment(CreateAppointmentRequest $request, Clinic $clinic): JsonResponse
    {
        $validated = $request->validated();

        $reservation = SlotReservation::query()
            ->where('session_token', $validated['session_token'])
            ->where('clinic_id', $clinic->id)
            ->whereNull('released_at')
            ->first();

        if (! $reservation || $reservation->expires_at->isPast()) {
            return response()->json(['code' => 'RESERVATION_EXPIRED', 'message' => 'Reservation has expired.'], 409);
        }

        $appointmentType = AppointmentType::query()->findOrFail($reservation->appointment_type_id);
        $insuranceData = [];

        if ($appointmentType->is_medical) {
            $insuranceData = $request->validate([
                'insurance_provider' => ['required', 'string', 'max:255'],
                'insurance_member_id' => ['required', 'string', 'max:255'],
                'insurance_group_id' => ['nullable', 'string', 'max:255'],
                'insurance_plan' => ['nullable', 'string', 'max:255'],
                'insurance_subscriber_name' => ['required', 'string', 'max:255'],
                'insurance_subscriber_dob' => ['required', 'date'],
                'insurance_relationship' => ['required', 'in:self,spouse,child,other'],
                'insurance_phone' => ['nullable', 'string', 'max:255'],
                'insurance_urgency' => ['required', 'in:standard,high,critical'],
            ]);
        }

        $patient = $this->patientMatchingService->findOrCreate([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'date_of_birth' => $validated['date_of_birth'],
            'communication_consent' => [
                'emailConsent' => true,
                'emailPHI' => $validated['email_phi'],
                'consentedAt' => now()->toIso8601String(),
                'consentIP' => $request->ip(),
            ],
        ], $clinic->id);

        $appointment = Appointment::query()->create([
            'clinic_id' => $clinic->id,
            'provider_id' => $reservation->provider_id,
            'appointment_type_id' => $reservation->appointment_type_id,
            'patient_id' => $patient->id,
            'slot_datetime' => $reservation->slot_datetime,
            'status' => 'confirmed',
        ]);

        $reservation->update([
            'converted_to_appointment_id' => $appointment->id,
            'released_at' => now(),
        ]);

        $this->appointmentCommunicationService->sendPostBookingEmail(
            $appointment,
            $patient,
            (bool) $validated['email_phi']
        );

        $payment = $this->appointmentPaymentService->initializePayment(
            $appointment,
            $appointmentType,
            $patient
        );

        if ($appointmentType->is_medical) {
            $this->insuranceVerificationService->createForAppointment($appointment, $patient, [
                'provider' => $insuranceData['insurance_provider'] ?? null,
                'member_id' => $insuranceData['insurance_member_id'] ?? null,
                'group_id' => $insuranceData['insurance_group_id'] ?? null,
                'plan' => $insuranceData['insurance_plan'] ?? null,
                'subscriber_name' => $insuranceData['insurance_subscriber_name'] ?? null,
                'subscriber_dob' => $insuranceData['insurance_subscriber_dob'] ?? null,
                'relationship' => $insuranceData['insurance_relationship'] ?? null,
                'phone' => $insuranceData['insurance_phone'] ?? null,
                'urgency' => $insuranceData['insurance_urgency'] ?? 'standard',
            ]);
        }

        return response()->json([
            'appointmentId' => $appointment->id,
            'providerId' => $appointment->provider_id,
            'slotUtc' => $appointment->slot_datetime,
            'payment' => $payment,
        ], 201);
    }

    private function providerSupportsAppointmentType(Provider $provider, int $appointmentTypeId): bool
    {
        $types = $provider->default_appointment_types ?? [];

        return in_array($appointmentTypeId, $types, true);
    }
}
