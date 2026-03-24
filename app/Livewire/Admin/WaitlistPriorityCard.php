<?php

namespace App\Livewire\Admin;

use App\Models\WaitlistEntry;
use Livewire\Component;

class WaitlistPriorityCard extends Component
{
    public int $urgentCount = 0;
    public int $highCount = 0;
    public int $standardCount = 0;
    public int $activeCount = 0;

    /** @var array<int, array{id:int,patient:string,score:int}> */
    public array $topUrgent = [];

    public function mount(): void
    {
        $this->ensureAdmin();
        $this->loadBreakdown();
    }

    public function render()
    {
        return view('livewire.admin.waitlist-priority-card');
    }

    private function loadBreakdown(): void
    {
        $tiers = WaitlistEntry::query()
            ->where('status', 'active')
            ->selectRaw('tier, count(*) as total')
            ->groupBy('tier')
            ->pluck('total', 'tier');

        $this->urgentCount = (int) ($tiers['urgent'] ?? 0);
        $this->highCount = (int) ($tiers['high'] ?? 0);
        $this->standardCount = (int) ($tiers['standard'] ?? 0);
        $this->activeCount = $this->urgentCount + $this->highCount + $this->standardCount;

        $this->topUrgent = WaitlistEntry::query()
            ->with('patient:id,full_name')
            ->where('status', 'active')
            ->where('tier', 'urgent')
            ->orderByDesc('priority_score')
            ->limit(3)
            ->get()
            ->map(fn (WaitlistEntry $entry): array => [
                'id' => $entry->id,
                'patient' => $entry->patient?->full_name ?? 'Patient',
                'score' => (int) $entry->priority_score,
            ])
            ->all();
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->is_admin, 403, 'Admin access required.');
    }
}
