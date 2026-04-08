<?php

namespace App\Livewire\Admin;

use App\Mail\WaitlistSlotAvailableEmail;
use App\Models\Clinic;
use App\Models\Appointment;
use App\Models\ProviderSchedule;
use App\Models\WaitlistEntry;
use App\Models\WaitlistNotification;
use App\Models\WaitlistNotificationRecipient;
use App\Services\SlotAvailabilityService;
use Carbon\CarbonImmutable;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Mail;

class WaitlistOffersPage extends Component
{
    use WithPagination;
    /** @var array<int, array{id:int,name:string,timezone:string}> */
    public array $clinics = [];

    /** @var \Illuminate\Pagination\LengthAwarePaginator|array<int, array<string,mixed>> */
    protected $notifications = [];

    public string $clinicFilter = 'all';
    public string $statusFilter = 'all';
    public string $search = '';
    public int $perPage = 10;

    /** @var array<int, string> */
    public array $offerLinks = [];

    public function mount(): void
    {
        $this->ensureAdmin();

        $this->clinics = Clinic::query()
            ->orderBy('name')
            ->get(['id', 'name', 'timezone'])
            ->map(fn (Clinic $clinic): array => [
                'id' => $clinic->id,
                'name' => $clinic->name,
                'timezone' => $clinic->timezone ?? 'UTC',
            ])->all();

        $requestedClinicId = request()->integer('clinic_id');
        if ($requestedClinicId > 0) {
            $this->clinicFilter = (string) $requestedClinicId;
        }

        $this->loadNotifications();
    }

    public function updatedClinicFilter(): void
    {
        $this->resetPage();
        $this->loadNotifications();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
        $this->loadNotifications();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->loadNotifications();
    }

    public function createOffer(int $notificationId, int $entryId): void
    {
        $this->ensureAdmin();

        $notification = WaitlistNotification::query()
            ->with(['clinic', 'appointmentType', 'provider', 'claimedAppointment'])
            ->findOrFail($notificationId);

        if ($notification->status !== 'pending') {
            $this->dispatch('toast', type: 'error', message: 'This slot is no longer pending.');

            return;
        }

        if (! $this->slotStillAvailable($notification)) {
            $notification->update(['status' => 'expired']);
            $this->dispatch('toast', type: 'error', message: 'This slot is no longer available.');
            $this->loadNotifications();

            return;
        }

        $eligible = $this->eligibleEntries($notification)->firstWhere('id', $entryId);
        if (! $eligible) {
            $this->dispatch('toast', type: 'error', message: 'That waitlist entry is no longer eligible for this slot.');

            return;
        }

        $expiresAt = now()->addMinutes(60);
        $entry = WaitlistEntry::query()->with('patient')->findOrFail($entryId);
        $issue = WaitlistNotificationRecipient::issue($notification, $entry, $expiresAt);

        $notification->update(['last_notified_at' => now()]);

        $link = route('waitlist.claim', ['token' => $issue['token']]);
        $this->offerLinks[$entryId] = $link;

        $patient = $entry->patient;
        $consent = $patient?->communication_consent ?? [];
        $hasConsent = (bool) ($consent['emailConsent'] ?? false);

        if ($patient && $patient->email && $hasConsent) {
            Mail::to($patient->email)->send(
                new WaitlistSlotAvailableEmail($issue['token'], $expiresAt)
            );
            $this->dispatch('toast', type: 'success', message: 'Offer link created and email sent.');
        } else {
            $this->dispatch('toast', type: 'success', message: 'Offer link created. Email not sent (missing consent or email).');
        }
        $this->loadNotifications();
    }

    private function loadNotifications(): void
    {
        $query = WaitlistNotification::query()
            ->with(['clinic:id,name,timezone', 'appointmentType:id,name,duration_minutes', 'provider:id,full_name'])
            ->orderBy('slot_datetime');

        if ($this->clinicFilter !== 'all') {
            $query->where('clinic_id', (int) $this->clinicFilter);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search !== '') {
            $search = '%'.strtolower($this->search).'%';
            $query->where(function ($sub) use ($search): void {
                $sub->whereHas('clinic', function ($clinicSub) use ($search): void {
                    $clinicSub->whereRaw('LOWER(name) LIKE ?', [$search]);
                })->orWhereHas('provider', function ($providerSub) use ($search): void {
                    $providerSub->whereRaw('LOWER(full_name) LIKE ?', [$search]);
                })->orWhereHas('appointmentType', function ($typeSub) use ($search): void {
                    $typeSub->whereRaw('LOWER(name) LIKE ?', [$search]);
                });
            });
        }

        $this->notifications = $query
            ->paginate($this->perPage)
            ->through(function (WaitlistNotification $notification): array {
                $clinic = $notification->clinic;
                $timezone = $clinic?->timezone ?? 'UTC';
                $slotLocal = $notification->slot_datetime
                    ? CarbonImmutable::parse($notification->slot_datetime)->setTimezone($timezone)->format('Y-m-d H:i')
                    : null;

                $eligible = $notification->status === 'pending'
                    ? $this->eligibleEntries($notification)
                    : collect();

                return [
                    'id' => $notification->id,
                    'status' => $notification->status,
                    'clinic' => $clinic?->name ?? 'Clinic',
                    'timezone' => $timezone,
                    'slot_local' => $slotLocal,
                    'provider' => $notification->provider?->full_name ?? 'Provider',
                    'appointment_type' => $notification->appointmentType?->name ?? 'Appointment',
                    'slot_duration' => $notification->appointmentType?->duration_minutes ?? null,
                    'eligible_entries' => $eligible,
                    'claimed_by_entry_id' => $notification->claimed_by_waitlist_entry_id,
                    'claimed_appointment_id' => $notification->claimed_appointment_id,
                ];
            });
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    private function eligibleEntries(WaitlistNotification $notification)
    {
        $clinic = $notification->clinic;
        $slotLocal = $notification->slot_datetime && $clinic
            ? CarbonImmutable::parse($notification->slot_datetime)->setTimezone($clinic->timezone)
            : null;
        $slotDate = $slotLocal?->toDateString() ?? now()->toDateString();
        $slotDuration = (int) ($notification->appointmentType?->duration_minutes ?? 0);
        $providerId = $notification->provider_id;

        $dayOfWeek = $slotLocal ? (int) $slotLocal->dayOfWeek : null;

        $schedules = [];
        if ($providerId && $dayOfWeek !== null) {
            $schedules = ProviderSchedule::query()
                ->where('provider_id', $providerId)
                ->where('is_active', true)
                ->where('day_of_week', $dayOfWeek)
                ->get();
        }

        $entries = WaitlistEntry::query()
            ->with(['patient:id,full_name,email,phone', 'appointmentType:id,name,duration_minutes'])
            ->where('clinic_id', $notification->clinic_id)
            ->where('status', 'active')
            ->whereHas('appointmentType', function ($typeSub) use ($slotDuration): void {
                $typeSub->where('duration_minutes', '<=', $slotDuration);
            })
            ->when($providerId, function ($query) use ($providerId): void {
                $query->where(function ($providerQuery) use ($providerId): void {
                    $providerQuery->whereNull('provider_id')->orWhere('provider_id', $providerId);
                });
            })
            ->orderByDesc('priority_score')
            ->orderBy('created_at')
            ->limit(15)
            ->get();

        $recipientMap = WaitlistNotificationRecipient::query()
            ->where('waitlist_notification_id', $notification->id)
            ->whereIn('waitlist_entry_id', $entries->pluck('id'))
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('waitlist_entry_id')
            ->map(fn ($group) => $group->first());

        if (! $slotLocal || $schedules === []) {
            return $entries->map(fn (WaitlistEntry $entry): array => $this->entryPayload(
                $entry,
                $slotDate,
                $recipientMap->get($entry->id)
            ));
        }

        return $entries->filter(function (WaitlistEntry $entry) use ($slotLocal, $schedules, $clinic): bool {
            $duration = (int) ($entry->appointmentType?->duration_minutes ?? 0);
            if ($duration <= 0) {
                return false;
            }

            $slotEnd = $slotLocal->addMinutes($duration);

            foreach ($schedules as $schedule) {
                if (! $this->scheduleAllowsType($schedule, $entry->appointment_type_id)) {
                    continue;
                }

                if ($schedule->effective_from && $slotLocal->toDateString() < $schedule->effective_from->toDateString()) {
                    continue;
                }

                if ($schedule->effective_until && $slotLocal->toDateString() > $schedule->effective_until->toDateString()) {
                    continue;
                }

                $start = CarbonImmutable::parse($slotLocal->toDateString().' '.$schedule->start_time, $clinic?->timezone ?? 'UTC');
                $end = CarbonImmutable::parse($slotLocal->toDateString().' '.$schedule->end_time, $clinic?->timezone ?? 'UTC');

                if ($slotLocal->greaterThanOrEqualTo($start) && $slotEnd->lessThanOrEqualTo($end)) {
                    return true;
                }
            }

            return false;
        })->values()->map(fn (WaitlistEntry $entry): array => $this->entryPayload(
            $entry,
            $slotDate,
            $recipientMap->get($entry->id)
        ));
    }

    /** @return array<string, mixed> */
    private function entryPayload(WaitlistEntry $entry, string $slotDate, ?WaitlistNotificationRecipient $recipient): array
    {
        $triage = $entry->triage_data ?? [];
        $window = $triage['preferred_time_window'] ?? null;
        $windowLabel = match ($window) {
            'morning' => 'Morning (9am-12pm)',
            'midday' => 'Midday (12pm-3pm)',
            'afternoon' => 'Afternoon (3pm-6pm)',
            'evening' => 'Evening (6pm-9pm)',
            default => 'Any time',
        };
        $preferredDate = $entry->preferred_datetime?->toDateString();
        $dateMatch = ! $preferredDate || $preferredDate === $slotDate;

        return [
            'id' => $entry->id,
            'patient' => $entry->patient?->full_name ?? 'Patient',
            'email' => $entry->patient?->email,
            'phone' => $entry->patient?->phone,
            'appointment_type' => $entry->appointmentType?->name ?? 'Appointment',
            'duration' => $entry->appointmentType?->duration_minutes,
            'priority_score' => $entry->priority_score,
            'priority_score_display' => number_format((float) $entry->priority_score, 1),
            'preferred_time_window' => $windowLabel,
            'preferred_date' => $preferredDate,
            'date_match' => $dateMatch,
            'offer_status' => $recipient?->status,
            'offer_sent_at' => $recipient?->notified_at?->format('Y-m-d H:i'),
            'offer_expires_at' => $recipient?->expires_at?->format('Y-m-d H:i'),
            'notes' => $triage['notes'] ?? null,
        ];
    }

    private function scheduleAllowsType(ProviderSchedule $schedule, ?int $appointmentTypeId): bool
    {
        if ($schedule->appointment_type_ids === null) {
            return true;
        }

        if (! $appointmentTypeId) {
            return false;
        }

        return in_array($appointmentTypeId, $schedule->appointment_type_ids, true);
    }

    private function slotStillAvailable(WaitlistNotification $notification): bool
    {
        $clinic = $notification->clinic;
        $provider = $notification->provider;
        $appointmentType = $notification->appointmentType;

        if (! $clinic || ! $provider || ! $appointmentType || ! $notification->slot_datetime) {
            return false;
        }

        $slotUtc = CarbonImmutable::parse($notification->slot_datetime)->utc();
        $slotConflict = \App\Models\Appointment::query()
            ->where('clinic_id', $clinic->id)
            ->where('provider_id', $provider->id)
            ->where('slot_datetime', $notification->slot_datetime)
            ->whereNotIn('status', ['canceled', 'no_show'])
            ->exists();

        if ($slotConflict) {
            return false;
        }

        return app(SlotAvailabilityService::class)
            ->isSlotAvailable($clinic, $provider, $appointmentType, $slotUtc);
    }

    public function render()
    {
        $this->loadNotifications();

        return view('livewire.admin.waitlist-offers-page', [
            'notifications' => $this->notifications,
        ]);
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->is_admin, 403, 'Admin access required.');
    }
}
