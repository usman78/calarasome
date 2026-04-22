<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProviderIndexRequest;
use App\Http\Requests\Admin\StoreProviderBlockedTimeRequest;
use App\Http\Requests\Admin\StoreProviderRequest;
use App\Http\Requests\Admin\UpdateProviderRequest;
use App\Http\Requests\Admin\UpdateProviderScheduleRequest;
use App\Models\Appointment;
use App\Models\Provider;
use App\Models\ProviderBlockedTime;
use App\Models\ProviderSchedule;
use Illuminate\Http\JsonResponse;

class AdminProviderController extends Controller
{
    public function index(ProviderIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Provider::class);
        $this->ensureClinicAccess((int) $request->integer('clinic_id'));

        $providers = Provider::query()
            ->where('clinic_id', (int) $request->integer('clinic_id'))
            ->orderBy('display_order')
            ->get();

        return response()->json($providers);
    }

    public function store(StoreProviderRequest $request): JsonResponse
    {
        $this->authorize('create', Provider::class);

        $validated = $request->validated();
        $this->ensureClinicAccess((int) $validated['clinic_id']);

        $provider = Provider::query()->create($validated);

        return response()->json($provider, 201);
    }

    public function update(UpdateProviderRequest $request, Provider $provider): JsonResponse
    {
        $this->authorize('update', $provider);

        $validated = $request->validated();

        if (array_key_exists('is_active', $validated) && $validated['is_active'] === false) {
            $activeCount = Provider::query()
                ->where('clinic_id', $provider->clinic_id)
                ->where('is_active', true)
                ->count();

            if ($provider->is_active && $activeCount <= 1) {
                return response()->json(['message' => 'At least one active provider is required per clinic.'], 422);
            }
        }

        $provider->update($validated);

        return response()->json($provider->fresh());
    }

    public function destroy(Provider $provider): JsonResponse
    {
        $this->authorize('delete', $provider);

        $hasAppointments = Appointment::query()
            ->where('provider_id', $provider->id)
            ->exists();

        if ($hasAppointments) {
            $activeCount = Provider::query()
                ->where('clinic_id', $provider->clinic_id)
                ->where('is_active', true)
                ->count();

            if ($provider->is_active && $activeCount <= 1) {
                return response()->json(['message' => 'At least one active provider is required per clinic.'], 422);
            }

            $provider->update(['is_active' => false]);

            return response()->json([
                'message' => 'Provider deactivated because appointment history exists.',
                'provider' => $provider->fresh(),
            ]);
        }

        $activeCount = Provider::query()
            ->where('clinic_id', $provider->clinic_id)
            ->where('is_active', true)
            ->count();

        if ($provider->is_active && $activeCount <= 1) {
            return response()->json(['message' => 'At least one active provider is required per clinic.'], 422);
        }

        $provider->delete();

        return response()->json(status: 204);
    }

    public function scheduleIndex(Provider $provider): JsonResponse
    {
        $this->authorize('view', $provider);

        return response()->json(
            ProviderSchedule::query()
                ->where('provider_id', $provider->id)
                ->orderBy('day_of_week')
                ->get()
        );
    }

    public function scheduleUpdate(UpdateProviderScheduleRequest $request, Provider $provider): JsonResponse
    {
        $this->authorize('update', $provider);

        $validated = $request->validated();

        ProviderSchedule::query()->where('provider_id', $provider->id)->delete();

        $rows = collect($validated['schedules'])->map(fn (array $row) => [
            'clinic_id' => $provider->clinic_id,
            'provider_id' => $provider->id,
            'day_of_week' => $row['day_of_week'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'appointment_type_ids' => $row['appointment_type_ids'] ?? null,
            'effective_from' => $row['effective_from'] ?? null,
            'effective_until' => $row['effective_until'] ?? null,
            'is_active' => $row['is_active'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        ProviderSchedule::query()->insert($rows);

        return response()->json($this->scheduleIndex($provider)->getData(true));
    }

    public function blockStore(StoreProviderBlockedTimeRequest $request, Provider $provider): JsonResponse
    {
        $this->authorize('update', $provider);

        $validated = $request->validated();

        $block = ProviderBlockedTime::query()->create([
            'provider_id' => $provider->id,
            'start_datetime' => $validated['start_datetime'],
            'end_datetime' => $validated['end_datetime'],
            'reason' => $validated['reason'] ?? null,
        ]);

        return response()->json($block, 201);
    }

    public function blockDestroy(Provider $provider, ProviderBlockedTime $block): JsonResponse
    {
        $this->authorize('update', $provider);

        if ($block->provider_id !== $provider->id) {
            return response()->json(['message' => 'Blocked range does not belong to provider.'], 422);
        }

        $block->delete();

        return response()->json(status: 204);
    }

    private function ensureClinicAccess(int $clinicId): void
    {
        abort_unless(auth()->user()?->canManageClinicId($clinicId), 403, 'Clinic access denied.');
    }
}
