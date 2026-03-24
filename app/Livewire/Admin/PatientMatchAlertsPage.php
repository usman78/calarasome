<?php

namespace App\Livewire\Admin;

use App\Models\Clinic;
use App\Models\PatientMatchAlert;
use App\Models\Patient;
use App\Services\PatientMergeService;
use Livewire\Component;
use Livewire\WithPagination;

class PatientMatchAlertsPage extends Component
{
    use WithPagination;
    /** @var array<int, array{id:int,name:string}> */
    public array $clinics = [];

    /** @var \Illuminate\Pagination\LengthAwarePaginator|array<int, array<string,mixed>> */
    public $alerts = [];

    public string $clinicFilter = 'all';
    public string $search = '';
    public int $perPage = 20;
    public int $openAlertsCount = 0;
    public int $resolvedAlertsCount = 0;
    public ?int $mergeTargetId = null;

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

        $requestedClinicId = request()->integer('clinic_id');
        if ($requestedClinicId > 0) {
            $this->clinicFilter = (string) $requestedClinicId;
        }

        $this->loadAlerts();
    }

    public function updatedClinicFilter(): void
    {
        $this->resetPage();
        $this->loadAlerts();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->loadAlerts();
    }

    public function markResolved(int $alertId): void
    {
        $this->ensureAdmin();

        $query = PatientMatchAlert::query()->whereNull('resolved_at');

        if ($this->clinicFilter !== 'all') {
            $query->where('clinic_id', (int) $this->clinicFilter);
        }

        $alert = $query->findOrFail($alertId);
        $alert->update(['resolved_at' => now()]);

        session()->flash('match_alerts_status', 'Alert marked as resolved.');
        $this->loadAlerts();
    }

    public function mergePatient(int $alertId, int $sourcePatientId, int $targetPatientId): void
    {
        $this->ensureAdmin();

        if ($sourcePatientId === $targetPatientId) {
            session()->flash('match_alerts_status', 'Select a different patient to merge into.');
            return;
        }

        $alert = PatientMatchAlert::query()->findOrFail($alertId);
        $source = Patient::query()->findOrFail($sourcePatientId);
        $target = Patient::query()->findOrFail($targetPatientId);

        app(PatientMergeService::class)->mergePatients($source, $target, auth()->id() ?? 0);

        $alert->update(['resolved_at' => now()]);

        session()->flash('match_alerts_status', 'Patients merged successfully.');
        $this->loadAlerts();
    }

    private function loadAlerts(): void
    {
        $query = PatientMatchAlert::query()
            ->with(['clinic:id,name', 'patient:id,full_name,date_of_birth,email'])
            ->orderByDesc('created_at');

        if ($this->clinicFilter !== 'all') {
            $query->where('clinic_id', (int) $this->clinicFilter);
        }

        if ($this->search) {
            $search = '%'.strtolower($this->search).'%';
            $query->where(function ($sub) use ($search): void {
                $sub->whereRaw('LOWER(alert_type) LIKE ?', [$search])
                    ->orWhereHas('patient', function ($patientSub) use ($search): void {
                        $patientSub->whereRaw('LOWER(full_name) LIKE ?', [$search])
                            ->orWhereRaw('LOWER(email) LIKE ?', [$search]);
                    })
                    ->orWhereRaw('LOWER(payload->>\"email\") LIKE ?', [$search]);
            });
        }

        $this->openAlertsCount = (clone $query)->whereNull('resolved_at')->count();
        $this->resolvedAlertsCount = (clone $query)->whereNotNull('resolved_at')->count();

        $this->alerts = $query
            ->paginate($this->perPage)
            ->through(function (PatientMatchAlert $alert): array {
                $existingIds = $alert->payload['existingPatientIds'] ?? [];

                return [
                    'id' => $alert->id,
                    'created_at' => $alert->created_at?->format('Y-m-d H:i:s'),
                    'clinic_name' => $alert->clinic?->name ?? 'Unknown clinic',
                    'patient_name' => $alert->patient?->full_name ?? 'Unknown patient',
                    'patient_dob' => $alert->patient?->date_of_birth?->format('Y-m-d'),
                    'email' => $alert->payload['email'] ?? $alert->patient?->email,
                    'alert_type' => $alert->alert_type,
                    'existing_patient_ids' => $existingIds,
                    'existing_patients' => Patient::query()
                        ->whereIn('id', $existingIds)
                        ->get(['id', 'full_name', 'date_of_birth'])
                        ->map(fn (Patient $patient): array => [
                            'id' => $patient->id,
                            'name' => $patient->full_name,
                            'dob' => $patient->date_of_birth?->format('Y-m-d'),
                        ])->all(),
                    'resolved_at' => $alert->resolved_at?->format('Y-m-d H:i:s'),
                ];
            });
    }

    public function render()
    {
        return view('livewire.admin.patient-match-alerts-page');
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->is_admin, 403, 'Admin access required.');
    }
}
