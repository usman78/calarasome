<?php

namespace App\Livewire\Admin;

use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Provider;
use App\Services\AppointmentTypeProviderMappingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class AppointmentTypesPage extends Component
{
    use WithPagination;
    /** @var array<int, array{id:int,name:string}> */
    public array $clinics = [];

    /** @var array<int, array{id:int,full_name:string,is_active:bool,default_appointment_types:array<int,int>}> */
    public array $providers = [];

    /** @var \Illuminate\Pagination\LengthAwarePaginator|array<int, array<string,mixed>> */
    public $appointmentTypes = [];

    public ?int $clinicId = null;
    public ?int $selectedAppointmentTypeId = null;
    public string $search = '';
    public int $perPage = 10;

    public string $name = '';
    public int $durationMinutes = 30;
    public bool $isActive = true;
    public bool $isMedical = false;
    public int $depositAmountCents = 0;
    public string $depositCurrency = 'usd';

    /** @var array<int, int> */
    public array $selectedProviderIds = [];

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

        $this->clinicId = request()->integer('clinic_id') ?: ($this->clinics[0]['id'] ?? null);

        $this->loadProviders();
        $this->loadAppointmentTypes();
        $this->selectFirstAppointmentTypeIfAvailable();
    }

    public function updatedClinicId(): void
    {
        $this->selectedAppointmentTypeId = null;
        $this->resetForm();
        $this->resetPage();
        $this->loadProviders();
        $this->loadAppointmentTypes();
        $this->selectFirstAppointmentTypeIfAvailable();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->loadAppointmentTypes();
    }

    public function selectAppointmentType(int $appointmentTypeId): void
    {
        $this->ensureAdmin();

        $appointmentType = AppointmentType::query()
            ->where('clinic_id', $this->clinicId)
            ->findOrFail($appointmentTypeId);

        $this->selectedAppointmentTypeId = $appointmentType->id;
        $this->name = $appointmentType->name;
        $this->durationMinutes = $appointmentType->duration_minutes;
        $this->isActive = (bool) $appointmentType->is_active;
        $this->isMedical = (bool) $appointmentType->is_medical;
        $this->depositAmountCents = (int) $appointmentType->deposit_amount_cents;
        $this->depositCurrency = (string) ($appointmentType->deposit_currency ?: 'usd');

        $this->selectedProviderIds = collect($this->providers)
            ->filter(function (array $provider) use ($appointmentType): bool {
                return in_array($appointmentType->id, $provider['default_appointment_types'] ?? [], true);
            })
            ->pluck('id')
            ->map(fn (int $id): int => (int) $id)
            ->values()
            ->all();
    }

    public function newAppointmentType(): void
    {
        $this->selectedAppointmentTypeId = null;
        $this->resetForm();
    }

    public function saveAppointmentType(): void
    {
        $this->ensureAdmin();

        $validated = $this->validate([
            'clinicId' => ['required', 'integer', 'exists:clinics,id'],
            'name' => ['required', 'string', 'max:255'],
            'durationMinutes' => ['required', 'integer', 'min:5', 'max:240'],
            'isActive' => ['boolean'],
            'isMedical' => ['boolean'],
            'depositAmountCents' => ['required', 'integer', 'min:0', 'max:1000000'],
            'depositCurrency' => ['required', 'string', 'max:10'],
            'selectedProviderIds' => ['array'],
            'selectedProviderIds.*' => [
                'integer',
                'distinct',
                Rule::exists('providers', 'id')->where(fn ($query) => $query->where('clinic_id', (int) $this->clinicId)),
            ],
        ]);

        $mappingService = app(AppointmentTypeProviderMappingService::class);

        $appointmentType = DB::transaction(function () use ($validated, $mappingService): AppointmentType {
            if ($this->selectedAppointmentTypeId) {
                $appointmentType = AppointmentType::query()
                    ->where('clinic_id', (int) $validated['clinicId'])
                    ->findOrFail($this->selectedAppointmentTypeId);

                $appointmentType->update([
                    'name' => $validated['name'],
                    'duration_minutes' => $validated['durationMinutes'],
                    'is_active' => (bool) $validated['isActive'],
                    'is_medical' => (bool) $validated['isMedical'],
                    'deposit_amount_cents' => (int) $validated['depositAmountCents'],
                    'deposit_currency' => $validated['depositCurrency'],
                ]);
            } else {
                $appointmentType = AppointmentType::query()->create([
                    'clinic_id' => (int) $validated['clinicId'],
                    'name' => $validated['name'],
                    'duration_minutes' => (int) $validated['durationMinutes'],
                    'is_active' => (bool) $validated['isActive'],
                    'is_medical' => (bool) $validated['isMedical'],
                    'deposit_amount_cents' => (int) $validated['depositAmountCents'],
                    'deposit_currency' => $validated['depositCurrency'],
                ]);
            }

            $mappingService->syncClinicProviders(
                (int) $validated['clinicId'],
                $appointmentType->id,
                $validated['selectedProviderIds'] ?? []
            );

            return $appointmentType->fresh();
        });

        $this->loadProviders();
        $this->loadAppointmentTypes();
        $this->selectAppointmentType($appointmentType->id);

        session()->flash('appointment_types_status', 'Appointment type saved.');
    }

    public function deleteAppointmentType(int $appointmentTypeId): void
    {
        $this->ensureAdmin();

        $appointmentType = AppointmentType::query()
            ->where('clinic_id', $this->clinicId)
            ->findOrFail($appointmentTypeId);

        DB::transaction(function () use ($appointmentType): void {
            app(AppointmentTypeProviderMappingService::class)->syncClinicProviders($appointmentType->clinic_id, $appointmentType->id, []);
            $appointmentType->delete();
        });

        $this->selectedAppointmentTypeId = null;
        $this->resetForm();
        $this->loadProviders();
        $this->loadAppointmentTypes();
        $this->selectFirstAppointmentTypeIfAvailable();

        session()->flash('appointment_types_status', 'Appointment type deleted.');
    }

    private function loadProviders(): void
    {
        if (! $this->clinicId) {
            $this->providers = [];

            return;
        }

        $this->providers = Provider::query()
            ->where('clinic_id', $this->clinicId)
            ->orderBy('display_order')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'is_active', 'default_appointment_types'])
            ->map(fn (Provider $provider): array => [
                'id' => $provider->id,
                'full_name' => $provider->full_name,
                'is_active' => (bool) $provider->is_active,
                'default_appointment_types' => array_values(
                    array_map('intval', $provider->default_appointment_types ?? [])
                ),
            ])
            ->all();
    }

    private function loadAppointmentTypes(): void
    {
        if (! $this->clinicId) {
            $this->appointmentTypes = [];

            return;
        }

        $providersByType = [];
        foreach ($this->providers as $provider) {
            foreach ($provider['default_appointment_types'] as $typeId) {
                $providersByType[$typeId][] = $provider['full_name'];
            }
        }

        $this->appointmentTypes = AppointmentType::query()
            ->where('clinic_id', $this->clinicId)
            ->when($this->search, function ($query): void {
                $search = '%'.strtolower($this->search).'%';
                $query->whereRaw('LOWER(name) LIKE ?', [$search]);
            })
            ->orderBy('name')
            ->paginate($this->perPage)
            ->through(function (AppointmentType $type) use ($providersByType): array {
                $names = $providersByType[$type->id] ?? [];

                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'duration_minutes' => $type->duration_minutes,
                    'is_active' => (bool) $type->is_active,
                    'provider_count' => count($names),
                    'provider_names' => $names,
                ];
            });
    }

    private function selectFirstAppointmentTypeIfAvailable(): void
    {
        if (! $this->selectedAppointmentTypeId && $this->appointmentTypes instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $first = $this->appointmentTypes->items()[0] ?? null;
            if ($first) {
                $this->selectAppointmentType((int) $first['id']);
            }
        }
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->durationMinutes = 30;
        $this->isActive = true;
        $this->isMedical = false;
        $this->depositAmountCents = 0;
        $this->depositCurrency = 'usd';
        $this->selectedProviderIds = [];
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->is_admin, 403, 'Admin access required.');
    }

    public function render()
    {
        return view('livewire.admin.appointment-types-page');
    }
}
