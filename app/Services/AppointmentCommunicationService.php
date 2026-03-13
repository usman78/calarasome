<?php

namespace App\Services;

use App\Mail\DeidentifiedAppointmentEmail;
use App\Mail\PhiAppointmentEmail;
use App\Models\Appointment;
use App\Models\AppointmentAccessToken;
use App\Models\Patient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;

class AppointmentCommunicationService
{
    public function sendPostBookingEmail(Appointment $appointment, Patient $patient, bool $emailPhi): void
    {
        $consent = $patient->communication_consent ?? [];
        $hasConsent = (bool) ($consent['emailConsent'] ?? false);

        if (! $hasConsent || ! $patient->email) {
            return;
        }

        if ($emailPhi) {
            $appointment->loadMissing(['clinic:id,name,timezone', 'provider:id,full_name', 'appointmentType:id,name']);
            $timezone = $appointment->clinic?->timezone ?? 'UTC';
            $slotLocal = CarbonImmutable::parse($appointment->slot_datetime)
                ->setTimezone($timezone)
                ->format('Y-m-d H:i:s');

            Mail::to($patient->email)->send(
                new PhiAppointmentEmail([
                    'clinic' => $appointment->clinic?->name ?? 'Clinic',
                    'provider' => $appointment->provider?->full_name ?? 'Provider',
                    'appointment_type' => $appointment->appointmentType?->name ?? 'Appointment',
                    'slot_local' => $slotLocal,
                    'timezone' => $timezone,
                ])
            );

            return;
        }

        $issued = AppointmentAccessToken::issue($appointment, $patient);

        Mail::to($patient->email)->send(
            new DeidentifiedAppointmentEmail(
                $issued['token'],
                $issued['record']->expires_at
            )
        );
    }
}
