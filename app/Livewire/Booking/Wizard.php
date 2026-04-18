<?php

namespace App\Livewire\Booking;

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Provider;
use App\Models\ProviderSchedule;
use App\Models\SlotReservation;
use App\Services\AppointmentCommunicationService;
use App\Services\AppointmentPaymentService;
use App\Services\ClinicDateTimeService;
use App\Services\EmailVerificationService;
use App\Services\InsuranceVerificationService;
use App\Services\PatientMatchingService;
use App\Services\ProviderAssignmentService;
use App\Services\SlotAvailabilityService;
use App\Services\WaitlistEntryService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Wizard extends Component
{
    public Clinic $clinic;

    /** @var array<int, array{id:int,name:string,duration_minutes:int,is_medical:bool}> */
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
    public string $emailVerificationCode = '';
    public string $phone = '';
    public string $dateOfBirth = '';
    public bool $emailConsent = true;
    public bool $emailPhi = false;
    public bool $emailVerified = false;
    public ?string $verifiedEmail = null;
    public ?string $emailVerificationSentTo = null;
    public ?string $emailVerificationSentAt = null;
    public bool $requiresInsurance = false;
    public string $insuranceProvider = '';
    public string $insuranceMemberId = '';
    public string $insuranceGroupId = '';
    public string $insurancePlan = '';
    public string $insuranceSubscriberName = '';
    public string $insuranceSubscriberDob = '';
    public string $insuranceRelationship = 'self';
    public string $insurancePhone = '';
    public string $insuranceUrgency = 'standard';
    public bool $isWaitlistMode = false;
    public ?string $preferredDate = null;
    public string $preferredTimeWindow = 'any';
    public string $waitlistNotes = '';
    public ?int $waitlistEntryId = null;
    public ?string $waitlistTier = null;
    public ?int $waitlistScore = null;
    public ?string $slotEmptyReason = null;
    public bool $canWaitlist = false;
    public bool $autoSelectedDate = false;

    public ?int $appointmentId = null;
    public ?string $confirmedSlotLocal = null;
    public ?string $paymentStrategy = null;
    public ?string $paymentStatus = null;
    public ?string $paymentClientSecret = null;

    public function mount(Clinic $clinic): void
    {
        $this->clinic = $clinic;
        $this->selectedDate = now($clinic->timezone)->addDay()->format('Y-m-d');
        $mappedTypeIds = Provider::query()
            ->where('clinic_id', $clinic->id)
            ->where('is_active', true)
            ->whereNotNull('default_appointment_types')
            ->get(['default_appointment_types'])
            ->flatMap(fn (Provider $provider) => $provider->default_appointment_types ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $this->appointmentTypes = AppointmentType::query()
            ->where('clinic_id', $clinic->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'duration_minutes', 'is_medical'])
            ->filter(fn (AppointmentType $type): bool => in_array($type->id, $mappedTypeIds, true))
            ->map(fn (AppointmentType $type): array => [
                'id' => $type->id,
                'name' => $type->name,
                'duration_minutes' => $type->duration_minutes,
                'is_medical' => (bool) $type->is_medical,
            ])->all();
    }

    public function chooseAppointmentType(int $appointmentTypeId): void
    {
        $this->appointmentTypeId = $appointmentTypeId;
        $this->providerSelection = null;
        $this->slotLocalDatetime = null;
        $this->sessionToken = null;
        $this->slotEmptyReason = null;
        $this->canWaitlist = false;
        $this->isWaitlistMode = false;
        $this->autoSelectedDate = false;
        $this->requiresInsurance = (bool) (collect($this->appointmentTypes)->firstWhere('id', $appointmentTypeId)['is_medical'] ?? false);
        if (! $this->requiresInsurance) {
            $this->resetInsuranceFields();
        }
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
        $this->slotEmptyReason = null;
        $this->canWaitlist = false;
        $this->isWaitlistMode = false;
        $nextAvailableDate = $this->findNextAvailableDateForSelection();
        if ($nextAvailableDate) {
            $this->selectedDate = $nextAvailableDate;
            $this->autoSelectedDate = true;
        } else {
            $this->autoSelectedDate = false;
        }
        $this->loadSlots();
        $this->step = 4;
    }

    public function updatedSelectedDate(): void
    {
        if ($this->step >= 4 && $this->providerSelection !== null) {
            $this->slotEmptyReason = null;
            $this->canWaitlist = false;
            $this->autoSelectedDate = false;
            $this->loadSlots();
        }
    }

    public function updatedEmail(): void
    {
        if ($this->normalizeEmail($this->email) !== $this->normalizeEmail($this->verifiedEmail)) {
            $this->emailVerified = false;
            $this->verifiedEmail = null;
            $this->emailVerificationCode = '';
            $this->emailVerificationSentTo = null;
            $this->emailVerificationSentAt = null;
        }
    }

    public function sendEmailVerificationCode(): void
    {
        $this->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $result = app(EmailVerificationService::class)->sendBookingCode($this->clinic, $this->email);

        if (! $result['sent']) {
            $this->addError('email', $result['message']);
            $this->dispatch('toast', type: 'error', message: $result['message']);

            return;
        }

        $this->resetErrorBag('email');
        $this->resetErrorBag('emailVerificationCode');
        $this->emailVerified = false;
        $this->verifiedEmail = null;
        $this->emailVerificationCode = '';
        $this->emailVerificationSentTo = $this->normalizeEmail($this->email);
        $this->emailVerificationSentAt = now()->format('Y-m-d H:i:s');
        $this->dispatch('toast', type: 'success', message: $result['message']);
    }

    public function verifyEmailCode(): void
    {
        $this->validate([
            'email' => ['required', 'email', 'max:255'],
            'emailVerificationCode' => ['required', 'digits:6'],
        ]);

        if ($this->normalizeEmail($this->email) !== $this->normalizeEmail($this->emailVerificationSentTo)) {
            $message = 'Request a fresh code for this email address before verifying.';
            $this->addError('emailVerificationCode', $message);
            $this->dispatch('toast', type: 'error', message: $message);

            return;
        }

        $verified = app(EmailVerificationService::class)->verifyBookingCode(
            $this->clinic,
            $this->email,
            $this->emailVerificationCode
        );

        if (! $verified) {
            $message = 'Invalid or expired verification code. Please request a new code.';
            $this->addError('emailVerificationCode', $message);
            $this->dispatch('toast', type: 'error', message: $message);

            return;
        }

        $this->resetErrorBag('emailVerificationCode');
        $this->emailVerified = true;
        $this->verifiedEmail = $this->normalizeEmail($this->email);
        $this->emailVerificationCode = '';
        $this->dispatch('toast', type: 'success', message: 'Email verified. You can continue now.');
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

        if (! $provider || ! $slotService->isSlotAvailable($this->clinic, $provider, $appointmentType, $slotUtc)) {
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
        if ($this->isWaitlistMode || ! $this->sessionToken || ! $this->reservationExpiresAt || $this->step !== 5) {
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
        $this->isWaitlistMode = false;
        $rules = [
            'fullName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'dateOfBirth' => ['required', 'date'],
            'emailConsent' => ['accepted'],
            'emailPhi' => ['boolean'],
        ];

        if ($this->requiresInsurance) {
            $rules = array_merge($rules, [
                'insuranceProvider' => ['required', 'string', 'max:255'],
                'insuranceMemberId' => ['required', 'string', 'max:255'],
                'insuranceGroupId' => ['nullable', 'string', 'max:255'],
                'insurancePlan' => ['nullable', 'string', 'max:255'],
                'insuranceSubscriberName' => ['required', 'string', 'max:255'],
                'insuranceSubscriberDob' => ['required', 'date'],
                'insuranceRelationship' => ['required', 'in:self,spouse,child,other'],
                'insurancePhone' => ['nullable', 'string', 'max:255'],
                'insuranceUrgency' => ['required', 'in:standard,high,critical'],
            ]);
        }

        $this->validate($rules);
        $this->ensureEmailVerified();

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

        if ($appointmentType->is_medical && $this->requiresInsurance) {
            app(InsuranceVerificationService::class)->createForAppointment($appointment, $patient, [
                'provider' => $this->insuranceProvider,
                'member_id' => $this->insuranceMemberId,
                'group_id' => $this->insuranceGroupId ?: null,
                'plan' => $this->insurancePlan ?: null,
                'subscriber_name' => $this->insuranceSubscriberName,
                'subscriber_dob' => $this->insuranceSubscriberDob,
                'relationship' => $this->insuranceRelationship,
                'phone' => $this->insurancePhone ?: null,
                'urgency' => $this->insuranceUrgency,
            ]);
        }

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
        $this->dispatch('toast', type: 'success', message: 'Appointment confirmed. You can review the details below.');
    }

    public function enterWaitlistMode(): void
    {
        $this->isWaitlistMode = true;
        $this->sessionToken = null;
        $this->reservationExpiresAt = null;
        $this->reservationSecondsRemaining = 0;
        $this->slotLocalDatetime = null;
        $this->assignedProviderId = null;
        $this->assignedProviderName = null;
        $this->slotEmptyReason = null;
        $this->preferredDate = $this->selectedDate ?: now($this->clinic->timezone)->format('Y-m-d');
        $this->preferredTimeWindow = 'any';
        $this->waitlistNotes = '';
        $this->step = 5;
    }

    public function submitWaitlist(): void
    {
        $this->validate([
            'fullName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'dateOfBirth' => ['required', 'date'],
            'emailConsent' => ['accepted'],
            'preferredDate' => ['nullable', 'date'],
            'preferredTimeWindow' => ['required', 'in:any,morning,midday,afternoon,evening'],
            'waitlistNotes' => ['nullable', 'string', 'max:500'],
        ]);
        $this->ensureEmailVerified();

        if (! $this->appointmentTypeId) {
            throw ValidationException::withMessages(['reservation' => 'Please select an appointment type first.']);
        }

        $providerId = null;
        if ($this->providerSelection && $this->providerSelection !== 'any') {
            $providerId = (int) $this->providerSelection;
        }

        $entry = app(WaitlistEntryService::class)->createEntry($this->clinic, [
            'appointment_type_id' => $this->appointmentTypeId,
            'provider_id' => $providerId,
            'full_name' => $this->fullName,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'date_of_birth' => $this->dateOfBirth,
            'email_phi' => $this->emailPhi,
            'preferred_date' => $this->preferredDate,
            'triage_data' => [
                'urgency_flag' => false,
                'preferred_time_window' => $this->preferredTimeWindow,
                'notes' => $this->waitlistNotes ?: null,
            ],
        ]);

        $this->waitlistEntryId = $entry->id;
        $this->waitlistTier = $entry->tier;
        $this->waitlistScore = $entry->priority_score;
        $this->step = 6;
        $this->dispatch('toast', type: 'success', message: 'You are on the waitlist. We will notify you if a spot opens.');
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
        $this->slotEmptyReason = null;
        $this->canWaitlist = false;

        if (! $this->appointmentTypeId || ! $this->providerSelection) {
            return;
        }

        $appointmentType = AppointmentType::query()
            ->where('clinic_id', $this->clinic->id)
            ->find($this->appointmentTypeId);

        if (! $appointmentType) {
            return;
        }

        $localDate = CarbonImmutable::parse($this->selectedDate.' 00:00:00', $this->clinic->timezone);
        $forDateUtc = $localDate->utc();
        $dayOfWeek = (int) $localDate->dayOfWeek;
        $slotService = app(SlotAvailabilityService::class);

        if ($this->providerSelection === 'any') {
            $providerIds = collect($this->providers)->pluck('id')->all();
            if ($providerIds === []) {
                $this->slotEmptyReason = 'No providers are mapped to this treatment yet. Add a provider in Admin -> Appointment Types.';

                return;
            }
            $providers = Provider::query()->whereIn('id', $providerIds)->get();
            $hasSchedule = ProviderSchedule::query()
                ->whereIn('provider_id', $providerIds)
                ->where('is_active', true)
                ->where('day_of_week', $dayOfWeek)
                ->get()
                ->filter(function (ProviderSchedule $schedule) use ($appointmentType, $localDate): bool {
                    if ($schedule->effective_from && $localDate->toDateString() < $schedule->effective_from->toDateString()) {
                        return false;
                    }

                    if ($schedule->effective_until && $localDate->toDateString() > $schedule->effective_until->toDateString()) {
                        return false;
                    }

                    if ($schedule->appointment_type_ids === null) {
                        return true;
                    }

                    return in_array($appointmentType->id, $schedule->appointment_type_ids, true);
                })
                ->isNotEmpty();

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

            if ($this->availableSlots === []) {
                if (! $hasSchedule) {
                    $this->slotEmptyReason = 'No providers are scheduled for this treatment on the selected date. Pick another day.';
                    $this->canWaitlist = false;
                } else {
                    $this->slotEmptyReason = 'Fully booked for the selected date. Join the waitlist and share your preferred time window.';
                    $this->canWaitlist = true;
                }
            }

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

        if ($this->availableSlots === []) {
            $hasSchedule = ProviderSchedule::query()
                ->where('provider_id', $provider->id)
                ->where('is_active', true)
                ->where('day_of_week', $dayOfWeek)
                ->get()
                ->filter(function (ProviderSchedule $schedule) use ($appointmentType, $localDate): bool {
                    if ($schedule->effective_from && $localDate->toDateString() < $schedule->effective_from->toDateString()) {
                        return false;
                    }

                    if ($schedule->effective_until && $localDate->toDateString() > $schedule->effective_until->toDateString()) {
                        return false;
                    }

                    if ($schedule->appointment_type_ids === null) {
                        return true;
                    }

                    return in_array($appointmentType->id, $schedule->appointment_type_ids, true);
                })
                ->isNotEmpty();

            if (! $hasSchedule) {
                $this->slotEmptyReason = 'This provider is not scheduled on the selected date. Pick another day.';
                $this->canWaitlist = false;
            } else {
                $this->slotEmptyReason = 'Fully booked for the selected date. Join the waitlist and share your preferred time window.';
                $this->canWaitlist = true;
            }
        }
    }

    private function findNextAvailableDateForSelection(): ?string
    {
        if (! $this->appointmentTypeId || ! $this->providerSelection) {
            return null;
        }

        $appointmentType = AppointmentType::query()
            ->where('clinic_id', $this->clinic->id)
            ->find($this->appointmentTypeId);

        if (! $appointmentType) {
            return null;
        }

        $slotService = app(SlotAvailabilityService::class);
        $startDate = now($this->clinic->timezone)->startOfDay();
        $searchDays = 30;

        for ($i = 0; $i <= $searchDays; $i++) {
            $localDate = $startDate->addDays($i);
            $forDateUtc = CarbonImmutable::parse($localDate->format('Y-m-d').' 00:00:00', $this->clinic->timezone)->utc();

            if ($this->providerSelection === 'any') {
                foreach ($this->providers as $provider) {
                    $providerModel = Provider::query()
                        ->where('clinic_id', $this->clinic->id)
                        ->find((int) $provider['id']);
                    if (! $providerModel) {
                        continue;
                    }
                    $slots = $slotService->availableSlots($this->clinic, $providerModel, $appointmentType, $forDateUtc);
                    if ($slots !== []) {
                        return $localDate->format('Y-m-d');
                    }
                }
            } else {
                $provider = Provider::query()
                    ->where('clinic_id', $this->clinic->id)
                    ->find((int) $this->providerSelection);
                if (! $provider) {
                    return null;
                }
                $slots = $slotService->availableSlots($this->clinic, $provider, $appointmentType, $forDateUtc);
                if ($slots !== []) {
                    return $localDate->format('Y-m-d');
                }
            }
        }

        return null;
    }

    private function resetInsuranceFields(): void
    {
        $this->insuranceProvider = '';
        $this->insuranceMemberId = '';
        $this->insuranceGroupId = '';
        $this->insurancePlan = '';
        $this->insuranceSubscriberName = '';
        $this->insuranceSubscriberDob = '';
        $this->insuranceRelationship = 'self';
        $this->insurancePhone = '';
        $this->insuranceUrgency = 'standard';
    }

    private function ensureEmailVerified(): void
    {
        if (! $this->emailVerified || $this->normalizeEmail($this->email) !== $this->normalizeEmail($this->verifiedEmail)) {
            throw ValidationException::withMessages([
                'emailVerificationCode' => 'Verify your email address before continuing.',
            ]);
        }
    }

    private function normalizeEmail(?string $email): string
    {
        return strtolower(trim((string) $email));
    }

    public function render()
    {
        return view('livewire.booking.wizard');
    }
}
