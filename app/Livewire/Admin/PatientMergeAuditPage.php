<?php

namespace App\Livewire\Admin;

use App\Models\Clinic;
use App\Models\PatientMergeLog;
use Livewire\Component;
use Livewire\WithPagination;

class PatientMergeAuditPage extends Component
{
    use WithPagination;
    /** @var array<int, array{id:int,name:string}> */
    public array $clinics = [];

    /** @var \Illuminate\Pagination\LengthAwarePaginator|array<int, array<string, mixed>> */
    public $merges = [];

    public string $clinicFilter = 'all';
    public string $search = '';
    public int $perPage = 15;

    public function mount(): void
    {
        $this->ensureAdmin();

        $this->clinics = Clinic::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Clinic $clinic): array => [
                'id' => $clinic->id,
                'name' => $clinic->name,
            ])->all();

        $this->loadMerges();
    }

    public function updatedClinicFilter(): void
    {
        $this->resetPage();
        $this->loadMerges();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->loadMerges();
    }

    private function loadMerges(): void
    {
        $query = PatientMergeLog::query()
            ->with([
                'clinic:id,name',
                'sourcePatient:id,full_name,date_of_birth,email',
                'targetPatient:id,full_name,date_of_birth,email',
                'mergedBy:id,name,email',
            ])
            ->orderByDesc('created_at');

        if ($this->clinicFilter !== 'all') {
            $query->where('clinic_id', (int) $this->clinicFilter);
        }

        if ($this->search !== '') {
            $search = '%'.strtolower($this->search).'%';
            $query->where(function ($sub) use ($search): void {
                $sub->whereHas('sourcePatient', function ($patientSub) use ($search): void {
                    $patientSub->whereRaw('LOWER(full_name) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$search]);
                })->orWhereHas('targetPatient', function ($patientSub) use ($search): void {
                    $patientSub->whereRaw('LOWER(full_name) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$search]);
                })->orWhereHas('mergedBy', function ($mergedSub) use ($search): void {
                    $mergedSub->whereRaw('LOWER(name) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$search]);
                });
            });
        }

        $this->merges = $query
            ->paginate($this->perPage)
            ->through(function (PatientMergeLog $log): array {
                return [
                    'id' => $log->id,
                    'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
                    'clinic' => $log->clinic?->name ?? 'Clinic',
                    'source' => [
                        'id' => $log->sourcePatient?->id,
                        'name' => $log->sourcePatient?->full_name ?? 'Unknown',
                        'dob' => $log->sourcePatient?->date_of_birth?->format('Y-m-d'),
                        'email' => $log->sourcePatient?->email,
                    ],
                    'target' => [
                        'id' => $log->targetPatient?->id,
                        'name' => $log->targetPatient?->full_name ?? 'Unknown',
                        'dob' => $log->targetPatient?->date_of_birth?->format('Y-m-d'),
                        'email' => $log->targetPatient?->email,
                    ],
                    'merged_by' => $log->mergedBy?->name ?? 'Admin',
                ];
            });
    }

    public function render()
    {
        return view('livewire.admin.patient-merge-audit-page');
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->is_admin, 403, 'Admin access required.');
    }
}
