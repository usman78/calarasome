<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\CancelAppointmentRequest;
use App\Models\Appointment;
use App\Models\AppointmentAccessToken;
use App\Services\AppointmentPaymentService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class PublicAppointmentController extends Controller
{
    public function cancel(
        CancelAppointmentRequest $request,
        Appointment $appointment,
        AppointmentPaymentService $paymentService
    ): JsonResponse {
        $validated = $request->validated();
        $token = $validated['token'];

        $record = AppointmentAccessToken::query()
            ->where('appointment_id', $appointment->id)
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (! $record) {
            return response()->json(['message' => 'Invalid cancellation token.'], 404);
        }

        if ($record->locked_until && now()->lessThan($record->locked_until)) {
            return response()->json(['message' => 'Cancellation link is locked.'], 423);
        }

        if ($record->expires_at->isPast()) {
            return response()->json(['message' => 'Cancellation link has expired.'], 410);
        }

        try {
            $result = $paymentService->cancelByPatient($appointment);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($result);
    }
}
