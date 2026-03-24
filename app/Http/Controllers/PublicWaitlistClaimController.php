<?php

namespace App\Http\Controllers;

use App\Http\Requests\Public\WaitlistClaimRequest;
use App\Models\WaitlistNotificationRecipient;
use App\Services\WaitlistNotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;

class PublicWaitlistClaimController extends Controller
{
    public function show(string $token): Response
    {
        $recipient = $this->findRecipient($token);

        if (! $recipient) {
            return response()->view('waitlist.claim', [
                'tokenStatus' => 'invalid',
            ], 404);
        }

        if ($recipient->expires_at->isPast()) {
            return response()->view('waitlist.claim', [
                'tokenStatus' => 'expired',
            ], 410);
        }

        if ($recipient->status === 'claimed' || $recipient->notification?->status === 'claimed') {
            $details = $this->buildDetails($recipient->notification?->claimedAppointment);

            return response()->view('waitlist.claim', [
                'tokenStatus' => 'claimed',
                'details' => $details,
            ]);
        }

        return response()->view('waitlist.claim', [
            'tokenStatus' => 'valid',
        ]);
    }

    public function verify(WaitlistClaimRequest $request, string $token): Response
    {
        $result = app(WaitlistNotificationService::class)->claim(
            $token,
            $request->validated('date_of_birth')
        );

        $status = $result['status'] ?? 'invalid';

        if ($status === 'claimed') {
            return response()->view('waitlist.claim', [
                'tokenStatus' => 'claimed',
                'details' => $result['details'] ?? null,
            ]);
        }

        return response()->view('waitlist.claim', [
            'tokenStatus' => $status,
        ], $status === 'invalid' ? 404 : 422);
    }

    private function findRecipient(string $token): ?WaitlistNotificationRecipient
    {
        return WaitlistNotificationRecipient::query()
            ->with(['notification.clinic', 'notification.claimedAppointment'])
            ->where('token_hash', hash('sha256', $token))
            ->first();
    }

    /** @return array<string, mixed>|null */
    private function buildDetails($appointment): ?array
    {
        if (! $appointment) {
            return null;
        }

        $appointment->loadMissing(['clinic:id,name,timezone', 'provider:id,full_name', 'appointmentType:id,name']);
        $timezone = $appointment->clinic?->timezone ?? 'UTC';
        $slotLocal = CarbonImmutable::parse($appointment->slot_datetime)
            ->setTimezone($timezone)
            ->format('Y-m-d H:i:s');

        return [
            'appointment_id' => $appointment->id,
            'clinic' => $appointment->clinic?->name ?? 'Clinic',
            'provider' => $appointment->provider?->full_name ?? 'Provider',
            'appointment_type' => $appointment->appointmentType?->name ?? 'Appointment',
            'slot_local' => $slotLocal,
            'timezone' => $timezone,
            'status' => $appointment->status ?? 'confirmed',
        ];
    }
}
