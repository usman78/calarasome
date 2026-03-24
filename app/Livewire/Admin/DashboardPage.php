<?php

namespace App\Livewire\Admin;

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Provider;
use Carbon\CarbonImmutable;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\Builder;

class DashboardPage extends Component
{
    use WithPagination;
    /** @var array<int, array{id:int,name:string}> */
    public array $clinics = [];

    /** @var array<int, array{id:int,name:string}> */
    public array $providers = [];

    /** @var array<int, array{id:int,name:string}> */
    public array $appointmentTypes = [];

    /** @var \Illuminate\Pagination\LengthAwarePaginator|array<int, array<string, mixed>> */
    public $appointments = [];

    public string $clinicFilter = 'all';
    public string $providerFilter = 'all';
    public string $appointmentTypeFilter = 'all';
    public string $statusFilter = 'all';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $search = '';
    public int $perPage = 15;

    public int $totalCount = 0;
    public int $statusConfirmed = 0;
    public int $statusPending = 0;
    public int $statusCanceled = 0;
    public int $statusNoShow = 0;
    public int $todayCount = 0;
    public int $next7Count = 0;

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

        $this->dateFrom = now()->toDateString();
        $this->dateTo = now()->addDays(7)->toDateString();

        $this->loadReferenceData();
        $this->loadAppointments();
    }

    public function updatedClinicFilter(): void
    {
        $this->resetPage();
        $this->providerFilter = 'all';
        $this->appointmentTypeFilter = 'all';
        $this->loadReferenceData();
        $this->loadAppointments();
    }

    public function updatedProviderFilter(): void
    {
        $this->resetPage();
        $this->loadAppointments();
    }

    public function updatedAppointmentTypeFilter(): void
    {
        $this->resetPage();
        $this->loadAppointments();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
        $this->loadAppointments();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
        $this->loadAppointments();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
        $this->loadAppointments();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->loadAppointments();
    }

    private function loadReferenceData(): void
    {
        $clinicId = $this->clinicFilter !== 'all' ? (int) $this->clinicFilter : null;

        $this->providers = Provider::query()
            ->when($clinicId, fn ($query) => $query->where('clinic_id', $clinicId))
            ->orderBy('full_name')
            ->get(['id', 'full_name'])
            ->map(fn (Provider $provider): array => [
                'id' => $provider->id,
                'name' => $provider->full_name,
            ])->all();

        $this->appointmentTypes = AppointmentType::query()
            ->when($clinicId, fn ($query) => $query->where('clinic_id', $clinicId))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (AppointmentType $type): array => [
                'id' => $type->id,
                'name' => $type->name,
            ])->all();
    }

    private function loadAppointments(): void
    {
        $baseQuery = $this->baseQuery();
        $filteredQuery = $this->applyDateRange(clone $baseQuery);
        $countQuery = $this->applyDateRange($this->baseQuery(false, false));

        $this->totalCount = (clone $countQuery)->count();

        $this->statusConfirmed = (clone $countQuery)->where('status', 'confirmed')->count();
        $this->statusPending = (clone $countQuery)->where('status', 'pending')->count();
        $this->statusCanceled = (clone $countQuery)->whereIn('status', ['cancelled_by_patient', 'cancelled_by_clinic'])->count();
        $this->statusNoShow = (clone $countQuery)->where('status', 'no_show')->count();

        $now = CarbonImmutable::now('UTC');
        $todayStart = $now->startOfDay();
        $todayEnd = $now->endOfDay();
        $next7End = $now->addDays(7)->endOfDay();
        $rangeQuery = $this->baseQuery(false, false);

        $this->todayCount = (clone $rangeQuery)
            ->whereBetween('slot_datetime', [$todayStart, $todayEnd])
            ->count();
        $this->next7Count = (clone $rangeQuery)
            ->whereBetween('slot_datetime', [$now, $next7End])
            ->count();

        $this->appointments = $filteredQuery
            ->paginate($this->perPage)
            ->through(function (Appointment $appointment): array {
                $timezone = $appointment->clinic?->timezone ?? 'UTC';
                $slotLocal = CarbonImmutable::parse($appointment->slot_datetime)
                    ->setTimezone($timezone)
                    ->format('Y-m-d H:i:s');

                return [
                    'id' => $appointment->id,
                    'clinic' => $appointment->clinic?->name ?? 'Clinic',
                    'provider' => $appointment->provider?->full_name ?? 'Provider',
                    'appointment_type' => $appointment->appointmentType?->name ?? 'Appointment',
                    'patient' => $appointment->patient?->full_name ?? 'Patient',
                    'slot_local' => $slotLocal,
                    'timezone' => $timezone,
                    'status' => $appointment->status ?? 'pending',
                    'payment_status' => $appointment->payment?->status ?? null,
                ];
            });
    }

    public function exportCsv(): StreamedResponse
    {
        $query = $this->applyDateRange($this->baseQuery());

        return $this->streamCsv($query, 'appointments-filtered');
    }

    public function exportNext7Days(): StreamedResponse
    {
        $now = CarbonImmutable::now('UTC');
        $next7End = $now->addDays(7)->endOfDay();
        $query = $this->baseQuery()
            ->whereBetween('slot_datetime', [$now, $next7End]);

        return $this->streamCsv($query, 'appointments-next-7-days');
    }

    private function baseQuery(bool $withRelations = true, bool $withOrder = true): Builder
    {
        $query = Appointment::query()
            ->select([
                'id',
                'clinic_id',
                'provider_id',
                'appointment_type_id',
                'patient_id',
                'slot_datetime',
                'status',
            ]);

        if ($withRelations) {
            $query->with([
                'clinic:id,name,timezone',
                'provider:id,full_name',
                'appointmentType:id,name',
                'patient:id,full_name',
                'payment:id,appointment_id,status',
            ]);
        }

        if ($withOrder) {
            $query->orderBy('slot_datetime');
        }

        if ($this->clinicFilter !== 'all') {
            $query->where('clinic_id', (int) $this->clinicFilter);
        }

        if ($this->providerFilter !== 'all') {
            $query->where('provider_id', (int) $this->providerFilter);
        }

        if ($this->appointmentTypeFilter !== 'all') {
            $query->where('appointment_type_id', (int) $this->appointmentTypeFilter);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search !== '') {
            $search = '%'.strtolower($this->search).'%';
            $query->where(function ($sub) use ($search): void {
                $sub->whereHas('patient', function ($patientSub) use ($search): void {
                    $patientSub->whereRaw('LOWER(full_name) LIKE ?', [$search]);
                })->orWhereHas('provider', function ($providerSub) use ($search): void {
                    $providerSub->whereRaw('LOWER(full_name) LIKE ?', [$search]);
                })->orWhereHas('appointmentType', function ($typeSub) use ($search): void {
                    $typeSub->whereRaw('LOWER(name) LIKE ?', [$search]);
                })->orWhereHas('clinic', function ($clinicSub) use ($search): void {
                    $clinicSub->whereRaw('LOWER(name) LIKE ?', [$search]);
                });
            });
        }

        return $query;
    }

    private function applyDateRange(Builder $query): Builder
    {
        if ($this->dateFrom) {
            $start = CarbonImmutable::parse($this->dateFrom.' 00:00:00', 'UTC')->utc();
            $query->where('slot_datetime', '>=', $start);
        }

        if ($this->dateTo) {
            $end = CarbonImmutable::parse($this->dateTo.' 23:59:59', 'UTC')->utc();
            $query->where('slot_datetime', '<=', $end);
        }

        return $query;
    }

    private function streamCsv(Builder $query, string $basename): StreamedResponse
    {
        $timestamp = now()->format('Ymd_His');
        $filename = "{$basename}_{$timestamp}.csv";

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Appointment ID',
                'Clinic',
                'Patient',
                'Provider',
                'Appointment Type',
                'Slot Local',
                'Timezone',
                'Status',
                'Payment Status',
            ]);

            $query->chunk(200, function ($rows) use ($handle): void {
                foreach ($rows as $appointment) {
                    $timezone = $appointment->clinic?->timezone ?? 'UTC';
                    $slotLocal = CarbonImmutable::parse($appointment->slot_datetime)
                        ->setTimezone($timezone)
                        ->format('Y-m-d H:i:s');

                    fputcsv($handle, [
                        $appointment->id,
                        $appointment->clinic?->name ?? 'Clinic',
                        $appointment->patient?->full_name ?? 'Patient',
                        $appointment->provider?->full_name ?? 'Provider',
                        $appointment->appointmentType?->name ?? 'Appointment',
                        $slotLocal,
                        $timezone,
                        $appointment->status ?? 'pending',
                        $appointment->payment?->status ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, $filename);
    }

    public function render()
    {
        return view('livewire.admin.dashboard-page');
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->is_admin, 403, 'Admin access required.');
    }
}
