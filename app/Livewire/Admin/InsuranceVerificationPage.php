<?php

namespace App\Livewire\Admin;

use App\Models\Clinic;
use App\Models\InsuranceVerification;
use App\Services\InsuranceVerificationService;
use Carbon\CarbonImmutable;
use Livewire\Component;
use Livewire\WithPagination;

class InsuranceVerificationPage extends Component
{
    use WithPagination;
    /** @var array<int, array{id:int,name:string}> */
    public array $clinics = [];

    /** @var \Illuminate\Pagination\LengthAwarePaginator|array<int, array<string, mixed>> */
    protected $verifications = [];

    public string $clinicFilter = 'all';
    public string $urgencyFilter = 'all';
    public string $statusFilter = 'pending';
    public string $search = '';
    public int $perPage = 15;

    public int $pendingCount = 0;
    public int $verifiedCount = 0;
    public int $failedCount = 0;

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

        $this->loadVerifications();
    }

    public function updatedClinicFilter(): void
    {
        $this->resetPage();
        $this->loadVerifications();
    }

    public function updatedUrgencyFilter(): void
    {
        $this->resetPage();
        $this->loadVerifications();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
        $this->loadVerifications();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->loadVerifications();
    }

    public function markVerified(int $verificationId): void
    {
        $this->ensureAdmin();

        $verification = InsuranceVerification::query()->findOrFail($verificationId);
        app(InsuranceVerificationService::class)->markVerified($verification);

        session()->flash('insurance_verification_status', 'Verification marked as verified.');
        $this->loadVerifications();
    }

    public function markFailed(int $verificationId): void
    {
        $this->ensureAdmin();

        $verification = InsuranceVerification::query()->findOrFail($verificationId);
        app(InsuranceVerificationService::class)->markFailed($verification);

        session()->flash('insurance_verification_status', 'Verification marked as failed and patient notified.');
        $this->loadVerifications();
    }

    private function loadVerifications(): void
    {
        $query = InsuranceVerification::query()
            ->with([
                'clinic:id,name,timezone',
                'patient:id,full_name,email',
                'appointment:id,clinic_id,provider_id,appointment_type_id,slot_datetime,status',
                'appointment.provider:id,full_name',
                'appointment.appointmentType:id,name',
            ]);

        if ($this->clinicFilter !== 'all') {
            $query->where('clinic_id', (int) $this->clinicFilter);
        }

        if ($this->urgencyFilter !== 'all') {
            $query->where('urgency', $this->urgencyFilter);
        }

        if ($this->search) {
            $search = '%'.strtolower($this->search).'%';
            $query->where(function ($sub) use ($search): void {
                $sub->whereHas('patient', function ($patientSub) use ($search): void {
                    $patientSub->whereRaw('LOWER(full_name) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$search]);
                })->orWhereRaw('LOWER(insurance_data->>\"provider\") LIKE ?', [$search]);
            });
        }

        $this->pendingCount = (clone $query)->where('status', 'pending')->count();
        $this->verifiedCount = (clone $query)->where('status', 'verified')->count();
        $this->failedCount = (clone $query)->where('status', 'failed')->count();

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $this->verifications = $query
            ->orderByRaw("CASE urgency WHEN 'critical' THEN 1 WHEN 'high' THEN 2 ELSE 3 END")
            ->orderBy('created_at')
            ->paginate($this->perPage)
            ->through(function (InsuranceVerification $verification): array {
                $appointment = $verification->appointment;
                $clinic = $verification->clinic;
                $timezone = $clinic?->timezone ?? 'UTC';
                $slotLocal = $appointment?->slot_datetime
                    ? CarbonImmutable::parse($appointment->slot_datetime)->setTimezone($timezone)->format('Y-m-d H:i:s')
                    : null;

                return [
                    'id' => $verification->id,
                    'created_at' => $verification->created_at?->format('Y-m-d H:i:s'),
                    'clinic' => $clinic?->name ?? 'Clinic',
                    'patient' => $verification->patient?->full_name ?? 'Patient',
                    'email' => $verification->patient?->email,
                    'urgency' => $verification->urgency,
                    'status' => $verification->status,
                    'appointment_type' => $appointment?->appointmentType?->name ?? 'Appointment',
                    'provider' => $appointment?->provider?->full_name ?? 'Provider',
                    'slot_local' => $slotLocal,
                    'timezone' => $timezone,
                    'insurance_provider' => $verification->insurance_data['provider'] ?? null,
                    'insurance_member_id' => $verification->insurance_data['member_id'] ?? null,
                ];
            });
    }

    public function render()
    {
        $this->loadVerifications();

        return view('livewire.admin.insurance-verification-page', [
            'verifications' => $this->verifications,
        ]);
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->is_admin, 403, 'Admin access required.');
    }
}
