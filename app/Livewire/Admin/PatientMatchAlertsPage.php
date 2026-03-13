<?php

namespace App\Livewire\Admin;

use App\Models\Clinic;
use App\Models\PatientMatchAlert;
use Livewire\Component;

class PatientMatchAlertsPage extends Component
{
    /** @var array<int, array{id:int,name:string}> */
    public array $clinics = [];

    /** @var array<int, array<string,mixed>> */
    public array $alerts = [];

    public string $clinicFilter = 'all';
    public int $openAlertsCount = 0;
    public int $resolvedAlertsCount = 0;

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

    private function loadAlerts(): void
    {
        $query = PatientMatchAlert::query()
            ->with(['clinic:id,name', 'patient:id,full_name,date_of_birth,email'])
            ->orderByDesc('created_at');

        if ($this->clinicFilter !== 'all') {
            $query->where('clinic_id', (int) $this->clinicFilter);
        }

        $this->openAlertsCount = (clone $query)->whereNull('resolved_at')->count();
        $this->resolvedAlertsCount = (clone $query)->whereNotNull('resolved_at')->count();

        $this->alerts = $query
            ->limit(200)
            ->get()
            ->map(function (PatientMatchAlert $alert): array {
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
                    'resolved_at' => $alert->resolved_at?->format('Y-m-d H:i:s'),
                ];
            })->all();
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
