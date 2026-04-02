<?php

namespace App\Livewire\Admin;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Services\AppointmentPaymentService;
use Carbon\CarbonImmutable;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class AppointmentsPage extends Component
{
    use WithPagination;
    /** @var array<int, array{id:int,name:string,timezone:string}> */
    public array $clinics = [];

    /** @var \Illuminate\Pagination\LengthAwarePaginator|array<int, array<string,mixed>> */
    protected $appointments = [];

    public string $clinicFilter = 'all';
    public string $dateFilter = 'today';
    public string $statusFilter = 'all';
    public string $search = '';
    public int $perPage = 20;

    public int $todayCount = 0;
    public int $futureCount = 0;
    public int $pastCount = 0;

    public ?string $actionError = null;
    public ?string $actionMessage = null;

    /** @var array<int, bool> */
    public array $openDetails = [];

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
        } elseif (! empty($this->clinics)) {
            $this->clinicFilter = (string) $this->clinics[0]['id'];
        }

        $this->loadAppointments();
    }

    public function updatedClinicFilter(): void
    {
        $this->resetPage();
        $this->loadAppointments();
    }

    public function updatedDateFilter(): void
    {
        $this->resetPage();
        $this->loadAppointments();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
        $this->loadAppointments();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->loadAppointments();
    }

    public function cancelAppointment(int $appointmentId): void
    {
        $this->ensureAdmin();
        $this->actionError = null;
        $this->actionMessage = null;

        $appointment = Appointment::query()->findOrFail($appointmentId);

        try {
            $result = app(AppointmentPaymentService::class)->cancelByClinic($appointment);
            $this->actionMessage = "Appointment cancelled ({$result['payment_action']}).";
            $this->dispatch('toast', type: 'success', message: $this->actionMessage);
        } catch (RuntimeException $exception) {
            $this->actionError = $exception->getMessage();
            $this->dispatch('toast', type: 'error', message: $this->actionError);
        }

        $this->loadAppointments();
    }

    public function markNoShow(int $appointmentId): void
    {
        $this->ensureAdmin();
        $this->actionError = null;
        $this->actionMessage = null;

        $appointment = Appointment::query()->findOrFail($appointmentId);

        try {
            $result = app(AppointmentPaymentService::class)->markNoShow($appointment, true);
            $this->actionMessage = "Appointment marked no-show ({$result['payment_action']}).";
            $this->dispatch('toast', type: 'success', message: $this->actionMessage);
        } catch (RuntimeException $exception) {
            $this->actionError = $exception->getMessage();
            $this->dispatch('toast', type: 'error', message: $this->actionError);
        }

        $this->loadAppointments();
    }

    public function toggleDetails(int $appointmentId): void
    {
        if (isset($this->openDetails[$appointmentId])) {
            unset($this->openDetails[$appointmentId]);
        } else {
            $this->openDetails[$appointmentId] = true;
        }
    }

    private function loadAppointments(): void
    {
        $query = Appointment::query()
            ->with([
                'clinic:id,name,timezone',
                'patient:id,full_name,email,phone',
                'provider:id,full_name',
                'appointmentType:id,name',
                'payment:id,appointment_id,status,amount_cents,currency,strategy',
                'insuranceVerification:id,appointment_id,status,urgency,insurance_data',
            ])
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
                $sub->whereHas('patient', function ($patientSub) use ($search): void {
                    $patientSub->whereRaw('LOWER(full_name) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$search]);
                })->orWhereHas('provider', function ($providerSub) use ($search): void {
                    $providerSub->whereRaw('LOWER(full_name) LIKE ?', [$search]);
                })->orWhereHas('appointmentType', function ($typeSub) use ($search): void {
                    $typeSub->whereRaw('LOWER(name) LIKE ?', [$search]);
                });
            });
        }

        [$todayStartUtc, $todayEndUtc] = $this->dateBoundsForFilter();

        $baseCounts = Appointment::query();
        if ($this->clinicFilter !== 'all') {
            $baseCounts->where('clinic_id', (int) $this->clinicFilter);
        }

        $this->todayCount = (clone $baseCounts)
            ->whereBetween('slot_datetime', [$todayStartUtc, $todayEndUtc])
            ->count();
        $this->pastCount = (clone $baseCounts)
            ->where('slot_datetime', '<', $todayStartUtc)
            ->count();
        $this->futureCount = (clone $baseCounts)
            ->where('slot_datetime', '>', $todayEndUtc)
            ->count();

        if ($this->dateFilter === 'today') {
            $query->whereBetween('slot_datetime', [$todayStartUtc, $todayEndUtc]);
        } elseif ($this->dateFilter === 'past') {
            $query->where('slot_datetime', '<', $todayStartUtc);
        } elseif ($this->dateFilter === 'future') {
            $query->where('slot_datetime', '>', $todayEndUtc);
        } elseif ($this->dateFilter === 'next7') {
            $query->whereBetween('slot_datetime', [$todayStartUtc, $todayEndUtc->addDays(7)]);
        }

        $nowUtc = now()->utc();

        $this->appointments = $query
            ->paginate($this->perPage)
            ->through(function (Appointment $appointment) use ($nowUtc): array {
                $clinic = $appointment->clinic;
                $timezone = $clinic?->timezone ?? 'UTC';
                $slotLocal = $appointment->slot_datetime
                    ? CarbonImmutable::parse($appointment->slot_datetime)->setTimezone($timezone)->format('Y-m-d H:i')
                    : null;
                $isFinal = in_array($appointment->status, ['cancelled_by_patient', 'cancelled_by_clinic', 'no_show'], true);
                $canNoShow = ! $isFinal && $appointment->slot_datetime && $appointment->slot_datetime->lessThan($nowUtc);

                return [
                    'id' => $appointment->id,
                    'status' => $appointment->status,
                    'clinic' => $clinic?->name ?? 'Clinic',
                    'timezone' => $timezone,
                    'slot_local' => $slotLocal,
                    'patient' => $appointment->patient?->full_name ?? 'Patient',
                    'email' => $appointment->patient?->email,
                    'phone' => $appointment->patient?->phone,
                    'provider' => $appointment->provider?->full_name ?? 'Provider',
                    'appointment_type' => $appointment->appointmentType?->name ?? 'Appointment',
                    'payment_status' => $appointment->payment?->status,
                    'insurance_status' => $appointment->insuranceVerification?->status,
                    'insurance_provider' => $appointment->insuranceVerification?->insurance_data['provider'] ?? null,
                    'insurance_member_id' => $appointment->insuranceVerification?->insurance_data['member_id'] ?? null,
                    'insurance_urgency' => $appointment->insuranceVerification?->urgency ?? null,
                    'can_cancel' => ! $isFinal,
                    'can_no_show' => $canNoShow,
                ];
            });
    }

    /** @return array{0:CarbonImmutable,1:CarbonImmutable} */
    private function dateBoundsForFilter(): array
    {
        $timezone = 'UTC';
        if ($this->clinicFilter !== 'all') {
            $clinic = collect($this->clinics)->firstWhere('id', (int) $this->clinicFilter);
            $timezone = $clinic['timezone'] ?? 'UTC';
        }

        $todayStartLocal = CarbonImmutable::now($timezone)->startOfDay();
        $todayEndLocal = CarbonImmutable::now($timezone)->endOfDay();

        return [$todayStartLocal->utc(), $todayEndLocal->utc()];
    }

    public function render()
    {
        $this->loadAppointments();

        return view('livewire.admin.appointments-page', [
            'appointments' => $this->appointments,
        ]);
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->is_admin, 403, 'Admin access required.');
    }
}
