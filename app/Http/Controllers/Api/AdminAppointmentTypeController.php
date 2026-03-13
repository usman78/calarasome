<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AppointmentTypeIndexRequest;
use App\Http\Requests\Admin\StoreAppointmentTypeRequest;
use App\Http\Requests\Admin\UpdateAppointmentTypeRequest;
use App\Models\AppointmentType;
use App\Services\AppointmentTypeProviderMappingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminAppointmentTypeController extends Controller
{
    public function __construct(
        private readonly AppointmentTypeProviderMappingService $mappingService,
    ) {
    }

    public function index(AppointmentTypeIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AppointmentType::class);

        return response()->json(
            AppointmentType::query()
                ->where('clinic_id', (int) $request->integer('clinic_id'))
                ->orderBy('name')
                ->get()
        );
    }

    public function store(StoreAppointmentTypeRequest $request): JsonResponse
    {
        $this->authorize('create', AppointmentType::class);

        $validated = $request->validated();

        $appointmentType = DB::transaction(function () use ($validated): AppointmentType {
            $appointmentType = AppointmentType::query()->create([
                'clinic_id' => $validated['clinic_id'],
                'name' => $validated['name'],
                'duration_minutes' => $validated['duration_minutes'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $this->mappingService->syncClinicProviders($appointmentType->clinic_id, $appointmentType->id, $validated['providerIds'] ?? []);

            return $appointmentType;
        });

        return response()->json($appointmentType->fresh(), 201);
    }

    public function update(UpdateAppointmentTypeRequest $request, AppointmentType $appointmentType): JsonResponse
    {
        $this->authorize('update', $appointmentType);

        $validated = $request->validated();

        $updatedType = DB::transaction(function () use ($appointmentType, $validated): AppointmentType {
            $appointmentType->update(collect($validated)->except('providerIds')->all());

            if (array_key_exists('providerIds', $validated)) {
                $this->mappingService->syncClinicProviders($appointmentType->clinic_id, $appointmentType->id, $validated['providerIds']);
            }

            return $appointmentType->fresh();
        });

        return response()->json($updatedType);
    }

    public function destroy(AppointmentType $appointmentType): JsonResponse
    {
        $this->authorize('delete', $appointmentType);

        DB::transaction(function () use ($appointmentType): void {
            $this->mappingService->syncClinicProviders($appointmentType->clinic_id, $appointmentType->id, []);
            $appointmentType->delete();
        });

        return response()->json(status: 204);
    }
}
