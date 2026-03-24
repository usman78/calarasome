<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\WaitlistEntry;
use Carbon\CarbonImmutable;

class WaitlistPriorityService
{
    /** @return array{score:int,tier:string,signals:array<string,mixed>} */
    public function calculate(WaitlistEntry $entry): array
    {
        $entry->loadMissing(['patient', 'clinic']);

        $triage = $entry->triage_data ?? [];
        $urgentFlag = (bool) ($triage['urgency_flag'] ?? false);

        $score = $urgentFlag ? 100 : 0;

        $completedCount = Appointment::query()
            ->where('clinic_id', $entry->clinic_id)
            ->where('patient_id', $entry->patient_id)
            ->where('status', 'completed')
            ->count();

        if ($completedCount > 0) {
            $score += 50;
        }

        $waitDays = min(30, max(0, CarbonImmutable::parse($entry->created_at)->diffInDays(now())));
        $score += $waitDays;

        if ($entry->preferred_datetime) {
            $score += 5;
        }

        if (($entry->patient?->no_show_count ?? 0) > 0) {
            $score -= 20;
        }

        if ($urgentFlag && $score < 100) {
            $score = 100;
        }

        $tier = $score >= 100 ? 'urgent' : ($score >= 50 ? 'high' : 'standard');

        return [
            'score' => $score,
            'tier' => $tier,
            'signals' => [
                'urgent_flag' => $urgentFlag,
                'completed_count' => $completedCount,
                'wait_days' => $waitDays,
                'preferred_datetime' => (bool) $entry->preferred_datetime,
                'no_show_penalty' => ($entry->patient?->no_show_count ?? 0) > 0,
            ],
        ];
    }

    public function refreshEntry(WaitlistEntry $entry): WaitlistEntry
    {
        $calc = $this->calculate($entry);

        $entry->update([
            'priority_score' => $calc['score'],
            'tier' => $calc['tier'],
        ]);

        return $entry->fresh();
    }
}
