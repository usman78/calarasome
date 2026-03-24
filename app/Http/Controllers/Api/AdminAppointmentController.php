<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelAppointmentRequest;
use App\Http\Requests\Admin\MarkNoShowRequest;
use App\Models\Appointment;
use App\Services\AppointmentPaymentService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class AdminAppointmentController extends Controller
{
    public function destroy(
        CancelAppointmentRequest $request,
        Appointment $appointment,
        AppointmentPaymentService $paymentService
    ): JsonResponse {
        $this->authorize('update', $appointment);

        try {
            $result = $paymentService->cancelByClinic($appointment);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($result);
    }

    public function markNoShow(
        MarkNoShowRequest $request,
        Appointment $appointment,
        AppointmentPaymentService $paymentService
    ): JsonResponse {
        $this->authorize('update', $appointment);

        $chargeDeposit = $request->boolean('charge_deposit', true);

        try {
            $result = $paymentService->markNoShow($appointment, $chargeDeposit);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($result);
    }
}
