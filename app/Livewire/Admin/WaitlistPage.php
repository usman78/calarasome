<?php

namespace App\Livewire\Admin;

use App\Models\Clinic;
use App\Models\WaitlistEntry;
use App\Services\WaitlistPriorityService;
use Carbon\CarbonImmutable;
use Livewire\Component;
use Livewire\WithPagination;

class WaitlistPage extends Component
{
    use WithPagination;
    /** @var array<int, array{id:int,name:string}> */
    public array $clinics = [];

    public string $clinicFilter = 'all';
    public string $search = '';
    public int $perPage = 15;

    /** @var \Illuminate\Pagination\LengthAwarePaginator|array<int, WaitlistEntry>|null */
    public $entryPaginator = null;

    /** @var array<string, array<int, array<string,mixed>>> */
    public array $entries = [
        'urgent' => [],
        'high' => [],
        'standard' => [],
    ];

    public function mount(WaitlistPriorityService $priorityService): void
    {
        $this->ensureAdmin();

        $this->clinics = Clinic::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Clinic $clinic): array => [
                'id' => $clinic->id,
                'name' => $clinic->name,
            ])->all();

        $this->loadEntries($priorityService);
    }

    public function updatedClinicFilter(WaitlistPriorityService $priorityService): void
    {
        $this->resetPage();
        $this->loadEntries($priorityService);
    }

    public function updatedSearch(WaitlistPriorityService $priorityService): void
    {
        $this->resetPage();
        $this->loadEntries($priorityService);
    }

    private function loadEntries(WaitlistPriorityService $priorityService): void
    {
        $query = WaitlistEntry::query()
            ->with(['clinic:id,name', 'patient:id,full_name,no_show_count', 'appointmentType:id,name'])
            ->where('status', 'active')
            ->orderByDesc('created_at');

        if ($this->clinicFilter !== 'all') {
            $query->where('clinic_id', (int) $this->clinicFilter);
        }

        if ($this->search !== '') {
            $search = '%'.strtolower($this->search).'%';
            $query->where(function ($sub) use ($search): void {
                $sub->whereHas('patient', function ($patientSub) use ($search): void {
                    $patientSub->whereRaw('LOWER(full_name) LIKE ?', [$search]);
                })->orWhereHas('appointmentType', function ($typeSub) use ($search): void {
                    $typeSub->whereRaw('LOWER(name) LIKE ?', [$search]);
                })->orWhereHas('clinic', function ($clinicSub) use ($search): void {
                    $clinicSub->whereRaw('LOWER(name) LIKE ?', [$search]);
                });
            });
        }

        $this->entryPaginator = $query->paginate($this->perPage);
        $entries = $this->entryPaginator->getCollection();

        $payload = [
            'urgent' => [],
            'high' => [],
            'standard' => [],
        ];

        foreach ($entries as $entry) {
            $entry = $priorityService->refreshEntry($entry);

            $payload[$entry->tier][] = [
                'id' => $entry->id,
                'clinic' => $entry->clinic?->name ?? 'Clinic',
                'patient' => $entry->patient?->full_name ?? 'Patient',
                'appointment_type' => $entry->appointmentType?->name ?? 'Appointment',
                'priority_score' => $entry->priority_score,
                'preferred_datetime' => $entry->preferred_datetime?->format('Y-m-d H:i'),
                'no_show_count' => $entry->patient?->no_show_count ?? 0,
                'created_at' => $entry->created_at?->format('Y-m-d H:i'),
                'wait_days' => CarbonImmutable::parse($entry->created_at)->diffInDays(now()),
            ];
        }

        $this->entries = $payload;
    }

    public function render()
    {
        return view('livewire.admin.waitlist-page');
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->is_admin, 403, 'Admin access required.');
    }
}
