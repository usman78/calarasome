<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\InsuranceVerification;
use App\Models\Patient;
use App\Models\User;
use App\Mail\InsuranceVerificationFailedEmail;
use App\Mail\InsuranceVerificationDailySummaryEmail;
use Carbon\CarbonImmutable;

class InsuranceVerificationService
{
    public function __construct(
        private readonly EmailDeliveryService $emailDeliveryService,
    ) {
    }

    /** @param array<string, mixed> $insurancePayload */
    public function createForAppointment(
        Appointment $appointment,
        Patient $patient,
        array $insurancePayload
    ): InsuranceVerification {
        $urgency = $this->normalizeUrgency($insurancePayload['urgency'] ?? 'standard');
        $alertedAt = in_array($urgency, ['critical', 'high'], true) ? now() : null;

        return InsuranceVerification::query()->create([
            'clinic_id' => $appointment->clinic_id,
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'status' => 'pending',
            'urgency' => $urgency,
            'insurance_data' => $insurancePayload,
            'alerted_at' => $alertedAt,
        ]);
    }

    public function markVerified(InsuranceVerification $verification): InsuranceVerification
    {
        if ($verification->status === 'verified') {
            return $verification;
        }

        $verification->update([
            'status' => 'verified',
            'verified_at' => now(),
            'failed_at' => null,
        ]);

        return $verification->fresh();
    }

    public function markFailed(InsuranceVerification $verification): InsuranceVerification
    {
        if ($verification->status === 'failed') {
            return $verification;
        }

        $verification->update([
            'status' => 'failed',
            'failed_at' => now(),
        ]);

        $this->sendFailureEmail($verification);

        return $verification->fresh();
    }

    private function normalizeUrgency(string $urgency): string
    {
        return in_array($urgency, ['critical', 'high', 'standard'], true)
            ? $urgency
            : 'standard';
    }

    public function sendDailySummaryForStandardUrgency(): int
    {
        $verifications = InsuranceVerification::query()
            ->with([
                'clinic:id,name,timezone',
                'patient:id,full_name,email',
                'appointment:id,clinic_id,provider_id,appointment_type_id,slot_datetime',
                'appointment.provider:id,full_name',
                'appointment.appointmentType:id,name',
            ])
            ->where('status', 'pending')
            ->where('urgency', 'standard')
            ->get();

        $items = [];

        foreach ($verifications as $verification) {
            $clinic = $verification->clinic;
            $timezone = $clinic?->timezone ?? 'UTC';
            $tomorrowDate = CarbonImmutable::now($timezone)->addDay()->toDateString();
            $slotLocalDate = $verification->appointment?->slot_datetime
                ? CarbonImmutable::parse($verification->appointment->slot_datetime)
                    ->setTimezone($timezone)
                    ->toDateString()
                : null;

            if (! $slotLocalDate || $slotLocalDate !== $tomorrowDate) {
                continue;
            }

            $slotLocal = CarbonImmutable::parse($verification->appointment->slot_datetime)
                ->setTimezone($timezone)
                ->format('Y-m-d H:i:s');

            $items[] = [
                'clinic' => $clinic?->name ?? 'Clinic',
                'patient' => $verification->patient?->full_name ?? 'Patient',
                'email' => $verification->patient?->email ?? null,
                'appointment_type' => $verification->appointment?->appointmentType?->name ?? 'Appointment',
                'provider' => $verification->appointment?->provider?->full_name ?? 'Provider',
                'slot_local' => $slotLocal,
                'timezone' => $timezone,
                'insurance_provider' => $verification->insurance_data['provider'] ?? null,
                'member_id' => $verification->insurance_data['member_id'] ?? null,
            ];
        }

        if ($items === []) {
            return 0;
        }

        $admins = User::query()
            ->where('is_admin', true)
            ->whereNotNull('email')
            ->get();

        foreach ($admins as $admin) {
            $this->emailDeliveryService->sendToAddress(
                null,
                null,
                $admin->email,
                new InsuranceVerificationDailySummaryEmail([
                    'report_date' => CarbonImmutable::now()->addDay()->format('Y-m-d'),
                    'items' => $items,
                ]),
                'insurance_verification_summary',
                null,
                ['admin_user_id' => $admin->id, 'items_count' => count($items)]
            );
        }

        return $admins->count();
    }

    private function sendFailureEmail(InsuranceVerification $verification): void
    {
        $verification->loadMissing(['patient', 'clinic']);

        $patient = $verification->patient;
        if (! $patient) {
            return;
        }

        $this->emailDeliveryService->sendPatientMail(
            $verification->clinic,
            $patient,
            new InsuranceVerificationFailedEmail([
                'clinic' => $verification->clinic?->name ?? 'Clinic',
                'patient' => $patient->full_name ?? 'Patient',
            ]),
            'insurance_verification',
            $verification->id,
            ['kind' => 'verification_failed']
        );
    }
}
