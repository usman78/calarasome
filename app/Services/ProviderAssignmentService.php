<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Provider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ProviderAssignmentService
{
    public function resolveProviderForAnyAvailable(int $clinicId, int $appointmentTypeId, CarbonImmutable $slotUtc): ?Provider
    {
        $providers = Provider::query()
            ->where('clinic_id', $clinicId)
            ->where('is_active', true)
            ->get()
            ->filter(function (Provider $provider) use ($appointmentTypeId): bool {
                $types = $provider->default_appointment_types ?? [];

                return in_array($appointmentTypeId, $types, true);
            });

        if ($providers->isEmpty()) {
            return null;
        }

        $dayStart = $slotUtc->startOfDay();
        $dayEnd = $slotUtc->endOfDay();

        $ranked = $providers->map(function (Provider $provider) use ($dayStart, $dayEnd) {
            $todayCount = Appointment::query()
                ->where('provider_id', $provider->id)
                ->whereBetween('slot_datetime', [$dayStart, $dayEnd])
                ->whereNotIn('status', ['cancelled_by_patient', 'cancelled_by_clinic'])
                ->count();

            return [
                'provider' => $provider,
                'count' => $todayCount,
            ];
        });

        /** @var Collection<int, array{provider: Provider, count: int}> $sorted */
        $sorted = $ranked->sort(function (array $a, array $b): int {
            if ($a['count'] !== $b['count']) {
                return $a['count'] <=> $b['count'];
            }

            $aAssigned = $a['provider']->last_auto_assigned_at;
            $bAssigned = $b['provider']->last_auto_assigned_at;

            if ($aAssigned === null && $bAssigned !== null) {
                return -1;
            }

            if ($aAssigned !== null && $bAssigned === null) {
                return 1;
            }

            if ($aAssigned && $bAssigned && ! $aAssigned->equalTo($bAssigned)) {
                return $aAssigned->lessThan($bAssigned) ? -1 : 1;
            }

            return $a['provider']->display_order <=> $b['provider']->display_order;
        })->values();

        $winner = $sorted->first()['provider'] ?? null;

        if ($winner) {
            $winner->forceFill(['last_auto_assigned_at' => now()])->save();
        }

        return $winner;
    }
}
