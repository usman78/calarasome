<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Provider;
use App\Models\ProviderBlockedTime;
use App\Models\ProviderSchedule;
use App\Models\SlotReservation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SlotAvailabilityService
{
    public function availableSlots(Clinic $clinic, Provider $provider, AppointmentType $appointmentType, CarbonImmutable $forDateUtc): array
    {
        $localDate = $forDateUtc->setTimezone($clinic->timezone);
        $dayOfWeek = (int) $localDate->dayOfWeek;

        $schedules = ProviderSchedule::query()
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
            });

        $slots = collect();

        foreach ($schedules as $schedule) {
            $slots = $slots->merge(
                $this->buildScheduleSlots($clinic, $localDate, $schedule->start_time, $schedule->end_time, $appointmentType->duration_minutes)
            );
        }

        return $slots
            ->filter(function (CarbonImmutable $slot) use ($clinic): bool {
                return $slot->greaterThanOrEqualTo(now()->addHours($clinic->min_booking_notice_hours)->utc());
            })
            ->filter(fn (CarbonImmutable $slot) => $this->isSlotAvailable($clinic, $provider, $slot))
            ->map(fn (CarbonImmutable $slot) => [
                'slotUtc' => $slot->toIso8601String(),
                'slotLocal' => $slot->setTimezone($clinic->timezone)->format('Y-m-d H:i:s'),
            ])
            ->values()
            ->all();
    }

    public function reserveSlot(Clinic $clinic, Provider $provider, AppointmentType $appointmentType, CarbonImmutable $slotUtc): SlotReservation
    {
        $token = Str::random(64);

        return SlotReservation::query()->create([
            'clinic_id' => $clinic->id,
            'provider_id' => $provider->id,
            'appointment_type_id' => $appointmentType->id,
            'slot_datetime' => $slotUtc,
            'session_token' => $token,
            'reserved_at' => now(),
            'expires_at' => now()->addMinutes(10),
            'released_at' => null,
        ]);
    }

    public function isSlotAvailable(Clinic $clinic, Provider $provider, CarbonImmutable $slotUtc): bool
    {
        if ($slotUtc->lessThan(now()->addHours($clinic->min_booking_notice_hours)->utc())) {
            return false;
        }

        $blocked = ProviderBlockedTime::query()
            ->where('provider_id', $provider->id)
            ->where('start_datetime', '<=', $slotUtc)
            ->where('end_datetime', '>', $slotUtc)
            ->exists();

        if ($blocked) {
            return false;
        }

        $hasAppointment = Appointment::query()
            ->where('provider_id', $provider->id)
            ->where('slot_datetime', $slotUtc)
            ->whereNotIn('status', ['cancelled_by_patient', 'cancelled_by_clinic'])
            ->exists();

        if ($hasAppointment) {
            return false;
        }

        $activeReservation = SlotReservation::query()
            ->where('provider_id', $provider->id)
            ->where('slot_datetime', $slotUtc)
            ->whereNull('released_at')
            ->where('expires_at', '>', now())
            ->exists();

        return ! $activeReservation;
    }

    private function buildScheduleSlots(Clinic $clinic, CarbonImmutable $localDate, string $startTime, string $endTime, int $durationMinutes): Collection
    {
        $start = CarbonImmutable::parse($localDate->toDateString().' '.$startTime, $clinic->timezone);
        $end = CarbonImmutable::parse($localDate->toDateString().' '.$endTime, $clinic->timezone);

        $slots = collect();
        $cursor = $start;

        while ($cursor->addMinutes($durationMinutes)->lessThanOrEqualTo($end)) {
            $slots->push($cursor->utc());
            $cursor = $cursor->addMinutes($durationMinutes);
        }

        return $slots;
    }
}
