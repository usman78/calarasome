<?php

namespace App\Services;

use App\Mail\DeidentifiedAppointmentEmail;
use App\Mail\AppointmentCancellationClinicEmail;
use App\Mail\AppointmentCancellationPatientEmail;
use App\Mail\AppointmentNoShowEmail;
use App\Mail\PhiAppointmentEmail;
use App\Models\Appointment;
use App\Models\AppointmentAccessToken;
use App\Models\Patient;
use Carbon\CarbonImmutable;
use Illuminate\Mail\Mailable;
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

    public function sendPatientCancellationEmail(Appointment $appointment, Patient $patient, string $policy): void
    {
        $details = $this->buildDetails($appointment, $patient);

        $subject = $policy === 'deposit_retained'
            ? 'Appointment Canceled - Deposit Retained'
            : ($policy === 'refund_issued' ? 'Appointment Canceled - Deposit Refunded' : 'Appointment Canceled - No Charge');

        $details['policy'] = $policy;
        $details['subject'] = $subject;

        $this->sendIfConsented($patient, new AppointmentCancellationPatientEmail($details));
    }

    public function sendClinicCancellationEmail(Appointment $appointment, Patient $patient, bool $refunded): void
    {
        $details = $this->buildDetails($appointment, $patient);
        $details['refund_note'] = $refunded
            ? 'Your deposit has been refunded. You should see the credit on your statement shortly.'
            : 'No charge has been made for this appointment.';
        $details['subject'] = $refunded
            ? 'Appointment Canceled - Deposit Refunded'
            : 'Appointment Canceled';

        $this->sendIfConsented($patient, new AppointmentCancellationClinicEmail($details));
    }

    public function sendNoShowEmail(Appointment $appointment, Patient $patient, int $amountCents): void
    {
        $appointment->loadMissing(['payment:id,appointment_id,currency']);
        $details = $this->buildDetails($appointment, $patient);
        $details['amount'] = $amountCents > 0
            ? number_format($amountCents / 100, 2).' '.strtoupper($appointment->payment?->currency ?? 'USD')
            : null;
        $details['subject'] = 'Missed Appointment - Deposit Charged';

        $this->sendIfConsented($patient, new AppointmentNoShowEmail($details));
    }

    private function sendIfConsented(Patient $patient, Mailable $mailable): void
    {
        $consent = $patient->communication_consent ?? [];
        $hasConsent = (bool) ($consent['emailConsent'] ?? false);

        if (! $hasConsent || ! $patient->email) {
            return;
        }

        Mail::to($patient->email)->send($mailable);
    }

    /** @return array<string, mixed> */
    private function buildDetails(Appointment $appointment, Patient $patient): array
    {
        $appointment->loadMissing(['clinic:id,name,timezone']);
        $timezone = $appointment->clinic?->timezone ?? 'UTC';
        $slotLocal = CarbonImmutable::parse($appointment->slot_datetime)
            ->setTimezone($timezone)
            ->format('Y-m-d H:i:s');

        return [
            'clinic' => $appointment->clinic?->name ?? 'Clinic',
            'patient' => $patient->full_name ?? 'Patient',
            'slot_local' => $slotLocal,
            'timezone' => $timezone,
        ];
    }
}
