<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WaitlistPriorityRequest;
use App\Models\WaitlistEntry;
use App\Services\WaitlistPriorityService;
use Illuminate\Http\JsonResponse;

class AdminWaitlistController extends Controller
{
    public function priorityBreakdown(
        WaitlistPriorityRequest $request,
        WaitlistPriorityService $priorityService
    ): JsonResponse {
        $this->authorize('viewAny', WaitlistEntry::class);

        $query = WaitlistEntry::query()
            ->with(['patient:id,full_name,no_show_count', 'clinic:id,name', 'appointmentType:id,name'])
            ->where('status', 'active');

        if ($request->filled('clinic_id')) {
            $clinicId = (int) $request->integer('clinic_id');
            $this->ensureClinicAccess($clinicId);
            $query->where('clinic_id', $clinicId);
        } else {
            $query->whereIn('clinic_id', $this->accessibleClinicIds());
        }

        $entries = $query->orderByDesc('created_at')->get();

        $payload = [
            'urgent' => [],
            'high' => [],
            'standard' => [],
        ];

        foreach ($entries as $entry) {
            $entry = $priorityService->refreshEntry($entry);

            $payload[$entry->tier][] = [
                'id' => $entry->id,
                'clinic' => $entry->clinic?->name ?? 'Clinic',
                'patient' => $entry->patient?->full_name ?? 'Patient',
                'appointment_type' => $entry->appointmentType?->name ?? 'Appointment',
                'priority_score' => $entry->priority_score,
                'preferred_datetime' => $entry->preferred_datetime?->format('Y-m-d H:i:s'),
                'no_show_count' => $entry->patient?->no_show_count ?? 0,
                'created_at' => $entry->created_at?->format('Y-m-d H:i:s'),
            ];
        }

        return response()->json($payload);
    }

    /** @return list<int> */
    private function accessibleClinicIds(): array
    {
        return auth()->user()?->clinics()->pluck('id')->map(fn ($id) => (int) $id)->all() ?? [];
    }

    private function ensureClinicAccess(int $clinicId): void
    {
        abort_unless(auth()->user()?->canManageClinicId($clinicId), 403, 'Clinic access denied.');
    }
}
