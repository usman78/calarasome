<?php

namespace App\Livewire\Admin;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Provider;
use App\Models\ProviderBlockedTime;
use App\Models\ProviderSchedule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class ProvidersPage extends Component
{
    use WithPagination;
    /** @var array<int, array{id:int,name:string}> */
    public array $clinics = [];

    /** @var \Illuminate\Pagination\LengthAwarePaginator|array<int, array<string,mixed>> */
    protected $providers = [];

    /** @var array<int, array<string,mixed>> */
    public array $blockedTimes = [];

    /** @var array<int, array<string,mixed>> */
    public array $schedules = [];

    public ?int $clinicId = null;
    public ?int $selectedProviderId = null;
    public string $search = '';
    public int $perPage = 10;

    public string $fullName = '';
    public string $title = '';
    public string $specialization = '';
    public string $email = '';
    public string $phone = '';
    public int $bookingBufferMinutes = 0;
    public int $displayOrder = 0;
    public bool $isActive = true;
    public bool $isAcceptingNewPatients = true;

    public string $blockStartDateTime = '';
    public string $blockEndDateTime = '';
    public string $blockReason = '';

    public function mount(): void
    {
        $this->clinics = Clinic::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Clinic $clinic): array => [
                'id' => $clinic->id,
                'name' => $clinic->name,
            ])->all();

        $this->clinicId = request()->integer('clinic_id') ?: ($this->clinics[0]['id'] ?? null);
        $this->loadProviders();
        $this->resetProviderForm();
        $this->selectFirstProviderIfAvailable();
    }

    public function updatedClinicId(): void
    {
        $this->selectedProviderId = null;
        $this->resetProviderForm();
        $this->resetPage();
        $this->loadProviders();
        $this->selectFirstProviderIfAvailable();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->loadProviders();
    }

    public function selectProvider(int $providerId): void
    {
        $provider = Provider::query()
            ->where('clinic_id', $this->clinicId)
            ->findOrFail($providerId);

        $this->selectedProviderId = $provider->id;
        $this->fullName = $provider->full_name;
        $this->title = $provider->title ?? '';
        $this->specialization = $provider->specialization ?? '';
        $this->email = $provider->email ?? '';
        $this->phone = $provider->phone ?? '';
        $this->bookingBufferMinutes = $provider->booking_buffer_minutes;
        $this->displayOrder = $provider->display_order;
        $this->isActive = (bool) $provider->is_active;
        $this->isAcceptingNewPatients = (bool) $provider->is_accepting_new_patients;

        $this->schedules = ProviderSchedule::query()
            ->where('provider_id', $provider->id)
            ->orderBy('day_of_week')
            ->get()
            ->map(function (ProviderSchedule $schedule): array {
                $start = $schedule->start_time;
                $end = $schedule->end_time;

                if (is_string($start) && strlen($start) >= 5) {
                    $start = substr($start, 0, 5);
                }
                if (is_string($end) && strlen($end) >= 5) {
                    $end = substr($end, 0, 5);
                }

                return [
                    'day_of_week' => $schedule->day_of_week,
                    'start_time' => $start,
                    'end_time' => $end,
                'effective_from' => $schedule->effective_from?->format('Y-m-d'),
                'effective_until' => $schedule->effective_until?->format('Y-m-d'),
                'is_active' => (bool) $schedule->is_active,
                ];
            })->all();

        if ($this->schedules === []) {
            $this->schedules[] = $this->newScheduleRow();
        }

        $this->blockedTimes = ProviderBlockedTime::query()
            ->where('provider_id', $provider->id)
            ->orderBy('start_datetime')
            ->get()
            ->map(fn (ProviderBlockedTime $block): array => [
                'id' => $block->id,
                'start_datetime' => $block->start_datetime->format('Y-m-d H:i:s'),
                'end_datetime' => $block->end_datetime->format('Y-m-d H:i:s'),
                'reason' => $block->reason,
            ])->all();
    }

    public function newProvider(): void
    {
        $this->selectedProviderId = null;
        $this->resetProviderForm();
    }

    public function saveProvider(): void
    {
        $validated = $this->validate([
            'clinicId' => ['required', 'integer', 'exists:clinics,id'],
            'fullName' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'bookingBufferMinutes' => ['required', 'integer', 'min:0', 'max:240'],
            'displayOrder' => ['required', 'integer', 'min:0'],
            'isActive' => ['boolean'],
            'isAcceptingNewPatients' => ['boolean'],
        ]);

        $this->ensureClinicAccess((int) $validated['clinicId']);

        if ($this->selectedProviderId) {
            $provider = Provider::query()->where('clinic_id', $this->clinicId)->findOrFail($this->selectedProviderId);

            if ($provider->is_active && ! $validated['isActive']) {
                $activeCount = Provider::query()->where('clinic_id', $this->clinicId)->where('is_active', true)->count();
                if ($activeCount <= 1) {
                    throw ValidationException::withMessages(['isActive' => 'At least one active provider is required.']);
                }
            }

            $provider->update([
                'full_name' => $validated['fullName'],
                'title' => $validated['title'] ?: null,
                'specialization' => $validated['specialization'] ?: null,
                'email' => $validated['email'] ?: null,
                'phone' => $validated['phone'] ?: null,
                'booking_buffer_minutes' => $validated['bookingBufferMinutes'],
                'display_order' => $validated['displayOrder'],
                'is_active' => $validated['isActive'],
                'is_accepting_new_patients' => $validated['isAcceptingNewPatients'],
            ]);
        } else {
            $provider = Provider::query()->create([
                'clinic_id' => $validated['clinicId'],
                'full_name' => $validated['fullName'],
                'title' => $validated['title'] ?: null,
                'specialization' => $validated['specialization'] ?: null,
                'email' => $validated['email'] ?: null,
                'phone' => $validated['phone'] ?: null,
                'booking_buffer_minutes' => $validated['bookingBufferMinutes'],
                'display_order' => $validated['displayOrder'],
                'is_active' => $validated['isActive'],
                'is_accepting_new_patients' => $validated['isAcceptingNewPatients'],
                'default_appointment_types' => [],
            ]);

            $this->selectedProviderId = $provider->id;
        }

        $this->loadProviders();
        $this->selectProvider($provider->id);
        session()->flash('providers_status', 'Provider saved.');
        $this->dispatch('toast', type: 'success', message: 'Provider saved.');
    }

    public function deleteProvider(int $providerId): void
    {
        $provider = Provider::query()->where('clinic_id', $this->clinicId)->findOrFail($providerId);

        $hasAppointments = Appointment::query()->where('provider_id', $provider->id)->exists();
        $activeCount = Provider::query()->where('clinic_id', $provider->clinic_id)->where('is_active', true)->count();

        if ($provider->is_active && $activeCount <= 1) {
            throw ValidationException::withMessages(['provider' => 'At least one active provider is required.']);
        }

        if ($hasAppointments) {
            $provider->update(['is_active' => false]);
            session()->flash('providers_status', 'Provider was deactivated (appointment history exists).');
            $this->dispatch('toast', type: 'success', message: 'Provider deactivated (appointments exist).');
        } else {
            $provider->delete();
            session()->flash('providers_status', 'Provider deleted.');
            $this->dispatch('toast', type: 'success', message: 'Provider deleted.');
        }

        $this->selectedProviderId = null;
        $this->resetProviderForm();
        $this->loadProviders();
    }

    public function addScheduleRow(): void
    {
        $this->schedules[] = $this->newScheduleRow();
    }

    public function removeScheduleRow(int $index): void
    {
        unset($this->schedules[$index]);
        $this->schedules = array_values($this->schedules);

        if ($this->schedules === []) {
            $this->schedules[] = $this->newScheduleRow();
        }
    }

    public function saveSchedules(): void
    {
        if (! $this->selectedProviderId) {
            throw ValidationException::withMessages(['provider' => 'Select a provider first.']);
        }

        $this->normalizeScheduleTimes();

        $this->validate([
            'schedules' => ['required', 'array', 'min:1'],
            'schedules.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'schedules.*.start_time' => ['required', 'date_format:H:i'],
            'schedules.*.end_time' => ['required', 'date_format:H:i'],
            'schedules.*.effective_from' => ['nullable', 'date'],
            'schedules.*.effective_until' => ['nullable', 'date'],
            'schedules.*.is_active' => ['boolean'],
        ]);

        $this->ensureNoScheduleOverlap();

        ProviderSchedule::query()->where('provider_id', $this->selectedProviderId)->delete();

        $rows = collect($this->schedules)->map(function (array $row): array {
            $start = $row['start_time'];
            $end = $row['end_time'];

            if (strlen($start) === 5) {
                $start .= ':00';
            }
            if (strlen($end) === 5) {
                $end .= ':00';
            }

            return [
                'clinic_id' => $this->clinicId,
                'provider_id' => $this->selectedProviderId,
                'day_of_week' => (int) $row['day_of_week'],
                'start_time' => $start,
                'end_time' => $end,
                'appointment_type_ids' => null,
                'effective_from' => $row['effective_from'] ?: null,
                'effective_until' => $row['effective_until'] ?: null,
                'is_active' => (bool) ($row['is_active'] ?? true),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->all();

        ProviderSchedule::query()->insert($rows);
        $this->selectProvider($this->selectedProviderId);
        session()->flash('providers_status', 'Schedule updated.');
        $this->dispatch('toast', type: 'success', message: 'Schedule updated.');
    }

    public function addBlockedTime(): void
    {
        if (! $this->selectedProviderId) {
            throw ValidationException::withMessages(['provider' => 'Select a provider first.']);
        }

        $validated = $this->validate([
            'blockStartDateTime' => ['required', 'date'],
            'blockEndDateTime' => ['required', 'date', 'after:blockStartDateTime'],
            'blockReason' => ['nullable', 'string', 'max:255'],
        ]);

        ProviderBlockedTime::query()->create([
            'provider_id' => $this->selectedProviderId,
            'start_datetime' => $validated['blockStartDateTime'],
            'end_datetime' => $validated['blockEndDateTime'],
            'reason' => $validated['blockReason'] ?: null,
        ]);

        $this->blockStartDateTime = '';
        $this->blockEndDateTime = '';
        $this->blockReason = '';

        $this->selectProvider($this->selectedProviderId);
        session()->flash('providers_status', 'Blocked period added.');
        $this->dispatch('toast', type: 'success', message: 'Blocked period added.');
    }

    public function deleteBlockedTime(int $blockedId): void
    {
        if (! $this->selectedProviderId) {
            return;
        }

        $block = ProviderBlockedTime::query()
            ->where('provider_id', $this->selectedProviderId)
            ->findOrFail($blockedId);

        $block->delete();

        $this->selectProvider($this->selectedProviderId);
        session()->flash('providers_status', 'Blocked period removed.');
        $this->dispatch('toast', type: 'success', message: 'Blocked period removed.');
    }

    private function loadProviders(): void
    {
        if (! $this->clinicId) {
            $this->providers = [];

            return;
        }

        $this->providers = Provider::query()
            ->where('clinic_id', $this->clinicId)
            ->when($this->search, function ($query): void {
                $search = '%'.strtolower($this->search).'%';
                $query->where(function ($sub) use ($search): void {
                    $sub->whereRaw('LOWER(full_name) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(title) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(specialization) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$search]);
                });
            })
            ->orderBy('display_order')
            ->paginate($this->perPage)
            ->through(fn (Provider $provider): array => [
                'id' => $provider->id,
                'full_name' => $provider->full_name,
                'title' => $provider->title,
                'is_active' => (bool) $provider->is_active,
                'is_accepting_new_patients' => (bool) $provider->is_accepting_new_patients,
                'display_order' => $provider->display_order,
            ]);
    }

    private function selectFirstProviderIfAvailable(): void
    {
        if (! $this->selectedProviderId && $this->providers instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $first = $this->providers->items()[0] ?? null;
            if ($first) {
                $this->selectProvider((int) $first['id']);
            }
        }
    }

    private function resetProviderForm(): void
    {
        $this->fullName = '';
        $this->title = '';
        $this->specialization = '';
        $this->email = '';
        $this->phone = '';
        $this->bookingBufferMinutes = 0;
        $this->displayOrder = 0;
        $this->isActive = true;
        $this->isAcceptingNewPatients = true;
        $this->schedules = [$this->newScheduleRow()];
        $this->blockedTimes = [];
        $this->blockStartDateTime = '';
        $this->blockEndDateTime = '';
        $this->blockReason = '';
    }

    /** @return array{day_of_week:int,start_time:string,end_time:string,effective_from:?string,effective_until:?string,is_active:bool} */
    private function newScheduleRow(): array
    {
        return [
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'effective_from' => null,
            'effective_until' => null,
            'is_active' => true,
        ];
    }

    private function normalizeScheduleTimes(): void
    {
        foreach ($this->schedules as $index => $row) {
            $start = $row['start_time'] ?? '';
            $end = $row['end_time'] ?? '';

            if (is_string($start) && strlen($start) >= 8) {
                $start = substr($start, 0, 5);
            }
            if (is_string($end) && strlen($end) >= 8) {
                $end = substr($end, 0, 5);
            }

            $this->schedules[$index]['start_time'] = $start;
            $this->schedules[$index]['end_time'] = $end;
        }
    }

    private function ensureNoScheduleOverlap(): void
    {
        $rows = array_values($this->schedules);

        for ($i = 0; $i < count($rows); $i++) {
            if ($rows[$i]['end_time'] <= $rows[$i]['start_time']) {
                throw ValidationException::withMessages([
                    "schedules.$i.end_time" => 'End time must be after start time.',
                ]);
            }

            for ($j = $i + 1; $j < count($rows); $j++) {
                if ((int) $rows[$i]['day_of_week'] !== (int) $rows[$j]['day_of_week']) {
                    continue;
                }

                $timeOverlap = $rows[$i]['start_time'] < $rows[$j]['end_time']
                    && $rows[$j]['start_time'] < $rows[$i]['end_time'];

                if (! $timeOverlap) {
                    continue;
                }

                $aFrom = $rows[$i]['effective_from'] ?: '0001-01-01';
                $aTo = $rows[$i]['effective_until'] ?: '9999-12-31';
                $bFrom = $rows[$j]['effective_from'] ?: '0001-01-01';
                $bTo = $rows[$j]['effective_until'] ?: '9999-12-31';

                $dateOverlap = $aFrom <= $bTo && $bFrom <= $aTo;

                if ($dateOverlap) {
                    throw ValidationException::withMessages([
                        'schedules' => 'Overlapping schedules are not allowed for the same day/date window.',
                    ]);
                }
            }
        }
    }

    public function render()
    {
        $this->loadProviders();

        return view('livewire.admin.providers-page', [
            'providers' => $this->providers,
        ]);
    }
}






