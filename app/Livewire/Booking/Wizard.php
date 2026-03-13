<?php

namespace App\Livewire\Booking;

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Provider;
use App\Models\SlotReservation;
use App\Services\AppointmentCommunicationService;
use App\Services\AppointmentPaymentService;
use App\Services\ClinicDateTimeService;
use App\Services\PatientMatchingService;
use App\Services\ProviderAssignmentService;
use App\Services\SlotAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Wizard extends Component
{
    public Clinic $clinic;

    /** @var array<int, array{id:int,name:string,duration_minutes:int}> */
    public array $appointmentTypes = [];

    /** @var array<int, array{id:int,full_name:string,title:?string}> */
    public array $providers = [];

    /** @var array<int, array{slotUtc:string,slotLocal:string,providerId:int,providerName:string}> */
    public array $availableSlots = [];

    public int $step = 1;
    public bool $isNewPatient = true;
    public ?int $appointmentTypeId = null;
    public ?string $providerSelection = null;
    public string $selectedDate = '';
    public ?string $slotLocalDatetime = null;

    public ?string $sessionToken = null;
    public ?string $reservationExpiresAt = null;
    public int $reservationSecondsRemaining = 0;

    public ?int $assignedProviderId = null;
    public ?string $assignedProviderName = null;

    public string $fullName = '';
    public string $email = '';
    public string $phone = '';
    public string $dateOfBirth = '';
    public bool $emailConsent = true;
    public bool $emailPhi = false;

    public ?int $appointmentId = null;
    public ?string $confirmedSlotLocal = null;
    public ?string $paymentStrategy = null;
    public ?string $paymentStatus = null;
    public ?string $paymentClientSecret = null;

    public function mount(Clinic $clinic): void
    {
        $this->clinic = $clinic;
        $this->selectedDate = now($clinic->timezone)->addDay()->format('Y-m-d');
        $this->appointmentTypes = AppointmentType::query()
            ->where('clinic_id', $clinic->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'duration_minutes'])
            ->map(fn (AppointmentType $type): array => [
                'id' => $type->id,
                'name' => $type->name,
                'duration_minutes' => $type->duration_minutes,
            ])->all();
    }

    public function chooseAppointmentType(int $appointmentTypeId): void
    {
        $this->appointmentTypeId = $appointmentTypeId;
        $this->providerSelection = null;
        $this->slotLocalDatetime = null;
        $this->sessionToken = null;
        $this->loadProviders();
        $this->step = 3;
    }

    public function updatedIsNewPatient(): void
    {
        if ($this->appointmentTypeId) {
            $this->loadProviders();
        }
    }

    public function chooseProvider(string $providerSelection): void
    {
        $this->providerSelection = $providerSelection;
        $this->slotLocalDatetime = null;
        $this->sessionToken = null;
        $this->loadSlots();
        $this->step = 4;
    }

    public function updatedSelectedDate(): void
    {
        if ($this->step >= 4 && $this->providerSelection !== null) {
            $this->loadSlots();
        }
    }

    public function reserveSlot(string $slotLocal): void
    {
        if (! $this->appointmentTypeId || ! $this->providerSelection) {
            throw ValidationException::withMessages(['slot' => 'Select appointment type and provider first.']);
        }

        $appointmentType = AppointmentType::query()
            ->where('clinic_id', $this->clinic->id)
            ->findOrFail($this->appointmentTypeId);

        $dateService = app(ClinicDateTimeService::class);
        $slotService = app(SlotAvailabilityService::class);

        try {
            $slotUtc = $dateService->parseClinicLocalToUtc($this->clinic, $slotLocal);
        } catch (\InvalidArgumentException) {
            throw ValidationException::withMessages(['slot' => 'Invalid slot selected.']);
        }

        $provider = $this->providerSelection === 'any'
            ? app(ProviderAssignmentService::class)->resolveProviderForAnyAvailable($this->clinic->id, $appointmentType->id, $slotUtc)
            : Provider::query()->where('clinic_id', $this->clinic->id)->find((int) $this->providerSelection);

        if (! $provider || ! $slotService->isSlotAvailable($this->clinic, $provider, $slotUtc)) {
            throw ValidationException::withMessages(['slot' => 'Selected slot is no longer available.']);
        }

        $reservation = $slotService->reserveSlot($this->clinic, $provider, $appointmentType, $slotUtc);

        $this->slotLocalDatetime = $slotLocal;
        $this->sessionToken = $reservation->session_token;
        $this->reservationExpiresAt = $reservation->expires_at->toIso8601String();
        $this->reservationSecondsRemaining = max(0, now()->diffInSeconds($reservation->expires_at, false));
        $this->assignedProviderId = $provider->id;
        $this->assignedProviderName = $provider->full_name;

        $this->step = 5;
    }

    public function refreshReservationTimer(): void
    {
        if (! $this->sessionToken || ! $this->reservationExpiresAt || $this->step !== 5) {
            return;
        }

        $reservation = SlotReservation::query()->where('session_token', $this->sessionToken)->first();

        if (! $reservation || $reservation->released_at || $reservation->expires_at->isPast()) {
            $this->addError('reservation', 'Your reservation has expired. Please pick a new slot.');
            $this->sessionToken = null;
            $this->reservationExpiresAt = null;
            $this->reservationSecondsRemaining = 0;
            $this->step = 4;

            return;
        }

        $this->reservationSecondsRemaining = max(0, now()->diffInSeconds($reservation->expires_at, false));
    }

    public function completeBooking(): void
    {
        $this->validate([
            'fullName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'dateOfBirth' => ['required', 'date'],
            'emailConsent' => ['accepted'],
            'emailPhi' => ['boolean'],
        ]);

        if (! $this->sessionToken) {
            throw ValidationException::withMessages(['reservation' => 'Slot reservation is required before booking.']);
        }

        $reservation = SlotReservation::query()
            ->where('session_token', $this->sessionToken)
            ->where('clinic_id', $this->clinic->id)
            ->whereNull('released_at')
            ->first();

        if (! $reservation || $reservation->expires_at->isPast()) {
            throw ValidationException::withMessages(['reservation' => 'Reservation has expired. Please reserve again.']);
        }

        $appointmentType = AppointmentType::query()
            ->where('clinic_id', $this->clinic->id)
            ->findOrFail($this->appointmentTypeId);

        $patient = app(PatientMatchingService::class)->findOrCreate([
            'full_name' => $this->fullName,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'date_of_birth' => $this->dateOfBirth,
            'communication_consent' => [
                'emailConsent' => true,
                'emailPHI' => $this->emailPhi,
                'consentedAt' => now()->toIso8601String(),
                'consentIP' => request()->ip(),
            ],
        ], $this->clinic->id);

        $appointment = Appointment::query()->create([
            'clinic_id' => $this->clinic->id,
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

        app(AppointmentCommunicationService::class)->sendPostBookingEmail(
            $appointment,
            $patient,
            $this->emailPhi
        );

        $payment = app(AppointmentPaymentService::class)->initializePayment(
            $appointment,
            $appointmentType,
            $patient
        );

        $this->paymentStrategy = $payment['strategy'] ?? null;
        $this->paymentStatus = $payment['status'] ?? null;
        $this->paymentClientSecret = $payment['client_secret'] ?? null;
        $this->dispatch('payment-ready');

        $this->appointmentId = $appointment->id;
        $this->confirmedSlotLocal = CarbonImmutable::parse($appointment->slot_datetime)->setTimezone($this->clinic->timezone)->format('Y-m-d H:i:s');
        $this->step = 6;
    }

    public function goToStep(int $step): void
    {
        $this->step = max(1, min(6, $step));
    }

    private function loadProviders(): void
    {
        if (! $this->appointmentTypeId) {
            $this->providers = [];

            return;
        }

        $this->providers = Provider::query()
            ->where('clinic_id', $this->clinic->id)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get()
            ->filter(function (Provider $provider): bool {
                $types = $provider->default_appointment_types ?? [];

                if (! in_array($this->appointmentTypeId, $types, true)) {
                    return false;
                }

                if ($this->isNewPatient && ! $provider->is_accepting_new_patients) {
                    return false;
                }

                return true;
            })
            ->map(fn (Provider $provider): array => [
                'id' => $provider->id,
                'full_name' => $provider->full_name,
                'title' => $provider->title,
            ])
            ->values()
            ->all();
    }

    private function loadSlots(): void
    {
        $this->availableSlots = [];

        if (! $this->appointmentTypeId || ! $this->providerSelection) {
            return;
        }

        $appointmentType = AppointmentType::query()
            ->where('clinic_id', $this->clinic->id)
            ->find($this->appointmentTypeId);

        if (! $appointmentType) {
            return;
        }

        $forDateUtc = CarbonImmutable::parse($this->selectedDate.' 00:00:00', $this->clinic->timezone)->utc();
        $slotService = app(SlotAvailabilityService::class);

        if ($this->providerSelection === 'any') {
            $providerIds = collect($this->providers)->pluck('id')->all();
            $providers = Provider::query()->whereIn('id', $providerIds)->get();

            $merged = [];
            foreach ($providers as $provider) {
                foreach ($slotService->availableSlots($this->clinic, $provider, $appointmentType, $forDateUtc) as $slot) {
                    $key = $slot['slotLocal'];
                    if (! isset($merged[$key])) {
                        $merged[$key] = [
                            'slotUtc' => $slot['slotUtc'],
                            'slotLocal' => $slot['slotLocal'],
                            'providerId' => $provider->id,
                            'providerName' => $provider->full_name,
                        ];
                    }
                }
            }

            ksort($merged);
            $this->availableSlots = array_values($merged);

            return;
        }

        $provider = Provider::query()->where('clinic_id', $this->clinic->id)->find((int) $this->providerSelection);
        if (! $provider) {
            return;
        }

        $this->availableSlots = collect($slotService->availableSlots($this->clinic, $provider, $appointmentType, $forDateUtc))
            ->map(fn (array $slot): array => [
                'slotUtc' => $slot['slotUtc'],
                'slotLocal' => $slot['slotLocal'],
                'providerId' => $provider->id,
                'providerName' => $provider->full_name,
            ])
            ->all();
    }

    public function render()
    {
        return view('livewire.booking.wizard');
    }
}
