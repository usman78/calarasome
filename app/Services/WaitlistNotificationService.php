<?php

namespace App\Services;

use App\Mail\WaitlistSlotAvailableEmail;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Provider;
use App\Models\WaitlistEntry;
use App\Models\WaitlistNotification;
use App\Models\WaitlistNotificationRecipient;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class WaitlistNotificationService
{
    /** @var array<int, array{tier:string,limit:int,delay_minutes:int,claim_minutes:int}> */
    public const ROUNDS = [
        ['tier' => 'urgent', 'limit' => 3, 'delay_minutes' => 20, 'claim_minutes' => 20],
        ['tier' => 'high', 'limit' => 5, 'delay_minutes' => 30, 'claim_minutes' => 30],
        ['tier' => 'standard', 'limit' => 10, 'delay_minutes' => 60, 'claim_minutes' => 60],
    ];

    public function __construct(
        private readonly SlotAvailabilityService $slotAvailabilityService
    ) {
    }

    public function createFromAppointment(Appointment $appointment): ?WaitlistNotification
    {
        $appointment->loadMissing(['clinic']);

        if (! $appointment->slot_datetime || $appointment->slot_datetime->isPast()) {
            return null;
        }

        if (! $appointment->appointment_type_id) {
            return null;
        }

        $minNoticeHours = (int) ($appointment->clinic?->min_booking_notice_hours ?? 0);
        if ($appointment->slot_datetime->lessThan(now()->addHours($minNoticeHours))) {
            return null;
        }

        $existing = WaitlistNotification::query()
            ->where('source_appointment_id', $appointment->id)
            ->whereIn('status', ['pending', 'claimed'])
            ->exists();

        if ($existing) {
            return null;
        }

        $appointmentType = AppointmentType::query()->find($appointment->appointment_type_id);
        if (! $appointmentType) {
            return null;
        }

        $slotDuration = (int) $appointmentType->duration_minutes;
        $slotDate = $appointment->slot_datetime->toDateString();

        $hasEntries = WaitlistEntry::query()
            ->where('clinic_id', $appointment->clinic_id)
            ->where('status', 'active')
            ->whereHas('appointmentType', function (Builder $query) use ($slotDuration): void {
                $query->where('duration_minutes', '<=', $slotDuration);
            })
            ->when($appointment->provider_id, function (Builder $query) use ($appointment): void {
                $query->where(function (Builder $providerQuery) use ($appointment): void {
                    $providerQuery->whereNull('provider_id')->orWhere('provider_id', $appointment->provider_id);
                });
            })
            ->where(function (Builder $query) use ($slotDate): void {
                $query->whereNull('preferred_datetime')->orWhereDate('preferred_datetime', $slotDate);
            })
            ->exists();

        if (! $hasEntries) {
            return null;
        }

        return WaitlistNotification::query()->create([
            'clinic_id' => $appointment->clinic_id,
            'appointment_type_id' => $appointment->appointment_type_id,
            'provider_id' => $appointment->provider_id,
            'source_appointment_id' => $appointment->id,
            'slot_datetime' => $appointment->slot_datetime,
            'status' => 'pending',
            'current_round' => 0,
            'next_round_at' => now(),
        ]);
    }

    public function dispatchDueNotifications(): int
    {
        $due = WaitlistNotification::query()
            ->where('status', 'pending')
            ->whereNotNull('next_round_at')
            ->where('next_round_at', '<=', now())
            ->get();

        $sent = 0;

        foreach ($due as $notification) {
            $sent += $this->dispatchNotification($notification);
        }

        return $sent;
    }

    public function dispatchNotification(WaitlistNotification $notification): int
    {
        if ($notification->status !== 'pending') {
            return 0;
        }

        $roundIndex = (int) $notification->current_round;
        $round = self::ROUNDS[$roundIndex] ?? null;

        if (! $round) {
            $notification->update([
                'status' => 'expired',
                'next_round_at' => null,
                'last_notified_at' => now(),
            ]);

            return 0;
        }

        $entries = $this->eligibleEntries($notification, $round['tier'])
            ->limit($round['limit'])
            ->get();

        foreach ($entries as $entry) {
            $this->notifyEntry($notification, $entry, $round['claim_minutes']);
        }

        $nextRound = self::ROUNDS[$roundIndex + 1] ?? null;

        if ($nextRound) {
            $notification->update([
                'current_round' => $roundIndex + 1,
                'next_round_at' => now()->addMinutes($nextRound['delay_minutes']),
                'last_notified_at' => now(),
            ]);
        } else {
            $notification->update([
                'status' => 'expired',
                'next_round_at' => null,
                'last_notified_at' => now(),
            ]);
        }

        return $entries->count();
    }

    /** @return array{status:string,details?:array<string,mixed>} */
    public function claim(string $token, string $dateOfBirth): array
    {
        $recipient = WaitlistNotificationRecipient::query()
            ->with(['waitlistEntry.patient', 'notification.clinic', 'notification.provider', 'notification.appointmentType'])
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (! $recipient) {
            return ['status' => 'invalid'];
        }

        if ($recipient->status === 'claimed') {
            return ['status' => 'claimed'];
        }

        if ($recipient->expires_at->isPast()) {
            $recipient->update(['status' => 'expired']);

            return ['status' => 'expired'];
        }

        $patient = $recipient->waitlistEntry?->patient;
        $dobMatch = $patient?->date_of_birth
            && $patient->date_of_birth->format('Y-m-d') === CarbonImmutable::parse($dateOfBirth)->format('Y-m-d');

        if (! $dobMatch) {
            return ['status' => 'invalid_dob'];
        }

        $result = DB::transaction(function () use ($recipient, $patient): array {
            $notification = WaitlistNotification::query()
                ->whereKey($recipient->waitlist_notification_id)
                ->lockForUpdate()
                ->first();

            if (! $notification || $notification->status !== 'pending') {
                return ['status' => 'claimed'];
            }

            $appointmentType = AppointmentType::query()->find($notification->appointment_type_id);
            $provider = $notification->provider_id
                ? Provider::query()->find($notification->provider_id)
                : null;

            if (! $appointmentType || ! $provider || ! $notification->slot_datetime) {
                $notification?->update([
                    'status' => 'expired',
                    'next_round_at' => null,
                ]);

                return ['status' => 'unavailable'];
            }

            if (! $this->slotAvailabilityService->isSlotAvailable($notification->clinic, $provider, $appointmentType, $notification->slot_datetime)) {
                $notification->update([
                    'status' => 'expired',
                    'next_round_at' => null,
                ]);

                $recipient->update(['status' => 'expired']);

                return ['status' => 'unavailable'];
            }

            $appointment = Appointment::query()->create([
                'clinic_id' => $notification->clinic_id,
                'provider_id' => $provider->id,
                'appointment_type_id' => $appointmentType->id,
                'patient_id' => $patient?->id,
                'slot_datetime' => $notification->slot_datetime,
                'status' => 'confirmed',
            ]);

            $notification->update([
                'status' => 'claimed',
                'claimed_by_waitlist_entry_id' => $recipient->waitlist_entry_id,
                'claimed_appointment_id' => $appointment->id,
                'claimed_at' => now(),
                'next_round_at' => null,
            ]);

            $recipient->update([
                'status' => 'claimed',
                'claimed_at' => now(),
            ]);

            WaitlistNotificationRecipient::query()
                ->where('waitlist_notification_id', $notification->id)
                ->where('id', '!=', $recipient->id)
                ->where('status', 'sent')
                ->update(['status' => 'canceled']);

            $recipient->waitlistEntry?->update(['status' => 'claimed']);

            $details = $this->buildDetails($notification, $appointment, $provider, $appointmentType);

            return [
                'status' => 'claimed',
                'details' => $details,
                'appointment' => $appointment,
                'appointment_type' => $appointmentType,
            ];
        });

        if (($result['status'] ?? '') !== 'claimed') {
            return $result;
        }

        $appointment = $result['appointment'];
        $appointmentType = $result['appointment_type'];

        if ($appointment && $appointmentType && $patient) {
            $consent = $patient->communication_consent ?? [];
            $emailPhi = (bool) ($consent['emailPHI'] ?? false);

            app(AppointmentCommunicationService::class)->sendPostBookingEmail(
                $appointment,
                $patient,
                $emailPhi
            );

            app(AppointmentPaymentService::class)->initializePayment(
                $appointment,
                $appointmentType,
                $patient
            );
        }

        return [
            'status' => 'claimed',
            'details' => $result['details'],
        ];
    }

    private function eligibleEntries(WaitlistNotification $notification, string $tier): Builder
    {
        $slotDate = $notification->slot_datetime
            ? $notification->slot_datetime->toDateString()
            : now()->toDateString();

        return WaitlistEntry::query()
            ->with(['patient'])
            ->where('clinic_id', $notification->clinic_id)
            ->where('appointment_type_id', $notification->appointment_type_id)
            ->where('status', 'active')
            ->where('tier', $tier)
            ->when($notification->provider_id, function (Builder $query) use ($notification): void {
                $query->where(function (Builder $providerQuery) use ($notification): void {
                    $providerQuery
                        ->whereNull('provider_id')
                        ->orWhere('provider_id', $notification->provider_id);
                });
            })
            ->where(function (Builder $query) use ($slotDate): void {
                $query
                    ->whereNull('preferred_datetime')
                    ->orWhereDate('preferred_datetime', $slotDate);
            })
            ->whereDoesntHave('notificationRecipients', function (Builder $query) use ($notification): void {
                $query->where('waitlist_notification_id', $notification->id);
            })
            ->orderByDesc('priority_score')
            ->orderBy('created_at');
    }

    private function notifyEntry(WaitlistNotification $notification, WaitlistEntry $entry, int $claimMinutes): void
    {
        $expiresAt = now()->addMinutes($claimMinutes);
        $issued = WaitlistNotificationRecipient::issue($notification, $entry, $expiresAt);

        $patient = $entry->patient;
        $consent = $patient?->communication_consent ?? [];
        $hasConsent = (bool) ($consent['emailConsent'] ?? false);

        if (! $patient || ! $patient->email || ! $hasConsent) {
            $issued['record']->update(['status' => 'canceled']);

            return;
        }

        Mail::to($patient->email)->send(
            new WaitlistSlotAvailableEmail($issued['token'], $expiresAt)
        );
    }

    /** @return array<string, mixed> */
    private function buildDetails(
        WaitlistNotification $notification,
        Appointment $appointment,
        Provider $provider,
        AppointmentType $appointmentType
    ): array {
        $notification->loadMissing(['clinic']);
        $timezone = $notification->clinic?->timezone ?? 'UTC';
        $slotLocal = CarbonImmutable::parse($appointment->slot_datetime)
            ->setTimezone($timezone)
            ->format('Y-m-d H:i:s');

        return [
            'appointment_id' => $appointment->id,
            'clinic' => $notification->clinic?->name ?? 'Clinic',
            'provider' => $provider->full_name ?? 'Provider',
            'appointment_type' => $appointmentType->name ?? 'Appointment',
            'slot_local' => $slotLocal,
            'timezone' => $timezone,
        ];
    }
}
