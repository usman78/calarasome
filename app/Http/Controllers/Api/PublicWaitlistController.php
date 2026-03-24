<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\CreateWaitlistEntryRequest;
use App\Models\Clinic;
use App\Models\Provider;
use App\Models\AppointmentType;
use App\Services\WaitlistEntryService;
use Illuminate\Http\JsonResponse;

class PublicWaitlistController extends Controller
{
    public function store(
        CreateWaitlistEntryRequest $request,
        Clinic $clinic,
        WaitlistEntryService $waitlistEntryService
    ): JsonResponse {
        $validated = $request->validated();

        $appointmentType = AppointmentType::query()
            ->where('clinic_id', $clinic->id)
            ->findOrFail((int) $validated['appointment_type_id']);

        if (! empty($validated['provider_id'])) {
            Provider::query()
                ->where('clinic_id', $clinic->id)
                ->findOrFail((int) $validated['provider_id']);
        }

        $entry = $waitlistEntryService->createEntry($clinic, [
            'appointment_type_id' => $appointmentType->id,
            'provider_id' => $validated['provider_id'] ?? null,
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'date_of_birth' => $validated['date_of_birth'],
            'email_phi' => (bool) ($validated['email_phi'] ?? false),
            'preferred_date' => $validated['preferred_date'] ?? null,
            'preferred_time' => $validated['preferred_time'] ?? null,
            'triage_data' => $validated['triage_data'] ?? [],
        ]);

        return response()->json([
            'id' => $entry->id,
            'tier' => $entry->tier,
            'priority_score' => $entry->priority_score,
            'status' => $entry->status,
        ], 201);
    }
}
