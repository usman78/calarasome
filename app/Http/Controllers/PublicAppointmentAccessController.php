<?php

namespace App\Http\Controllers;

use App\Models\AppointmentAccessToken;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PublicAppointmentAccessController extends Controller
{
    public function show(string $token): Response
    {
        $record = $this->findTokenRecord($token);

        if (! $record) {
            return response()->view('appointments.secure', [
                'tokenStatus' => 'invalid',
            ], 404);
        }

        if ($record->expires_at->isPast()) {
            return response()->view('appointments.secure', [
                'tokenStatus' => 'expired',
            ], 410);
        }

        if ($record->locked_until && now()->lessThan($record->locked_until)) {
            return response()->view('appointments.secure', [
                'tokenStatus' => 'locked',
            ], 423);
        }

        $verified = (bool) session()->get($this->sessionKey($record));
        $details = null;

        if ($verified) {
            $appointment = $record->appointment()
                ->with(['provider:id,full_name', 'appointmentType:id,name', 'clinic:id,name,timezone'])
                ->first();

            if ($appointment) {
                $slotLocal = CarbonImmutable::parse($appointment->slot_datetime)
                    ->setTimezone($appointment->clinic?->timezone ?? 'UTC')
                    ->format('Y-m-d H:i:s');

                $details = [
                    'clinic' => $appointment->clinic?->name ?? 'Clinic',
                    'provider' => $appointment->provider?->full_name ?? 'Provider',
                    'appointment_type' => $appointment->appointmentType?->name ?? 'Appointment',
                    'slot_local' => $slotLocal,
                    'timezone' => $appointment->clinic?->timezone ?? 'UTC',
                ];
            }
        }

        return response()->view('appointments.secure', [
            'tokenStatus' => 'valid',
            'verified' => $verified,
            'details' => $details,
        ]);
    }

    public function verify(Request $request, string $token): Response
    {
        $validated = $request->validate([
            'date_of_birth' => ['required', 'date'],
        ]);

        $record = $this->findTokenRecord($token);

        if (! $record) {
            return response()->view('appointments.secure', [
                'tokenStatus' => 'invalid',
            ], 404);
        }

        if ($record->expires_at->isPast()) {
            return response()->view('appointments.secure', [
                'tokenStatus' => 'expired',
            ], 410);
        }

        if ($record->locked_until && now()->lessThan($record->locked_until)) {
            return response()->view('appointments.secure', [
                'tokenStatus' => 'locked',
            ], 423);
        }

        $patient = $record->patient;
        $dobMatch = $patient?->date_of_birth?->format('Y-m-d') === CarbonImmutable::parse($validated['date_of_birth'])->format('Y-m-d');

        if (! $dobMatch) {
            $failed = (int) $record->failed_attempts + 1;
            $lockedUntil = $failed >= 3 ? $record->expires_at : null;

            $record->update([
                'failed_attempts' => min($failed, 255),
                'locked_until' => $lockedUntil,
            ]);

            $status = $lockedUntil ? 'locked' : 'invalid_dob';

            return response()->view('appointments.secure', [
                'tokenStatus' => $status,
            ], $lockedUntil ? 423 : 422);
        }

        $record->update([
            'failed_attempts' => 0,
            'locked_until' => null,
        ]);

        session()->put($this->sessionKey($record), true);

        return $this->show($token);
    }

    private function findTokenRecord(string $token): ?AppointmentAccessToken
    {
        return AppointmentAccessToken::query()
            ->with(['patient:id,date_of_birth', 'appointment'])
            ->where('token_hash', hash('sha256', $token))
            ->first();
    }

    private function sessionKey(AppointmentAccessToken $record): string
    {
        return 'appointment_access_granted.'.$record->id;
    }
}
