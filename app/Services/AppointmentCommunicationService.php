<?php

namespace App\Services;

use App\Mail\DeidentifiedAppointmentEmail;
use App\Mail\AppointmentCancellationClinicEmail;
use App\Mail\AppointmentCancellationPatientEmail;
use App\Mail\AppointmentNoShowEmail;
use App\Mail\AppointmentNoShowReversedEmail;
use App\Mail\PhiAppointmentEmail;
use App\Models\Appointment;
use App\Models\AppointmentAccessToken;
use App\Models\Patient;
use Carbon\CarbonImmutable;
use Illuminate\Mail\Mailable;

class AppointmentCommunicationService
{
    public function __construct(
        private readonly EmailDeliveryService $emailDeliveryService,
    ) {
    }

    public function sendPostBookingEmail(Appointment $appointment, Patient $patient, bool $emailPhi): void
    {
        $consent = $patient->communication_consent ?? [];
        $hasConsent = (bool) ($consent['emailConsent'] ?? false);

        if (! $hasConsent || ! $patient->email) {
            $this->emailDeliveryService->logSkipped(
                $appointment->clinic,
                $patient,
                $patient->email,
                $emailPhi ? PhiAppointmentEmail::class : DeidentifiedAppointmentEmail::class,
                $patient->email ? 'missing_consent' : 'missing_email',
                'appointment',
                $appointment->id,
                ['kind' => 'post_booking']
            );
            return;
        }

        if ($emailPhi) {
            $appointment->loadMissing(['clinic:id,name,timezone', 'provider:id,full_name', 'appointmentType:id,name,is_medical,deposit_amount_cents,deposit_currency']);
            $timezone = $appointment->clinic?->timezone ?? 'UTC';
            $slotLocal = CarbonImmutable::parse($appointment->slot_datetime)
                ->setTimezone($timezone)
                ->format('Y-m-d H:i:s');
            $depositRequired = $this->depositRequired($appointment->appointmentType);
            $freeCancelUntil = $depositRequired ? $this->freeCancelUntilLocal($appointment, $timezone) : null;
            $freeCancelDeadline = app(\App\Services\AppointmentPaymentService::class)->freeCancelDeadline($appointment);
            $cancellationNotFree = $freeCancelDeadline ? now()->greaterThan($freeCancelDeadline) : false;

            $this->emailDeliveryService->sendPatientMail(
                $appointment->clinic,
                $patient,
                new PhiAppointmentEmail([
                    'clinic' => $appointment->clinic?->name ?? 'Clinic',
                    'provider' => $appointment->provider?->full_name ?? 'Provider',
                    'appointment_type' => $appointment->appointmentType?->name ?? 'Appointment',
                    'slot_local' => $slotLocal,
                    'timezone' => $timezone,
                    'free_cancel_until' => $freeCancelUntil,
                    'cancellation_not_free' => $cancellationNotFree,
                    'deposit_required' => $depositRequired,
                ]),
                'appointment',
                $appointment->id,
                ['kind' => 'post_booking_phi']
            );

            return;
        }

        $issued = AppointmentAccessToken::issue($appointment, $patient);

        $appointment->loadMissing(['appointmentType:id,is_medical,deposit_amount_cents,deposit_currency', 'clinic:id,name,timezone']);
        $depositRequired = $this->depositRequired($appointment->appointmentType);
        $freeCancelUntil = $depositRequired ? $this->freeCancelUntilLocal($appointment, $appointment->clinic?->timezone ?? 'UTC') : null;
        $freeCancelDeadline = app(\App\Services\AppointmentPaymentService::class)->freeCancelDeadline($appointment);
        $cancellationNotFree = $freeCancelDeadline ? now()->greaterThan($freeCancelDeadline) : false;

        $this->emailDeliveryService->sendPatientMail(
            $appointment->clinic,
            $patient,
            new DeidentifiedAppointmentEmail(
                $issued['token'],
                $issued['record']->expires_at,
                $freeCancelUntil,
                $cancellationNotFree,
                $depositRequired
            ),
            'appointment',
            $appointment->id,
            ['kind' => 'post_booking_secure_link']
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

    public function sendNoShowReversedEmail(Appointment $appointment, Patient $patient, bool $refunded): void
    {
        $details = $this->buildDetails($appointment, $patient);
        $details['refund_note'] = $refunded
            ? 'This was marked in error and your deposit has been refunded.'
            : 'This was marked in error. No deposit will be retained.';

        $this->sendIfConsented($patient, new AppointmentNoShowReversedEmail($details));
    }

    private function sendIfConsented(Patient $patient, Mailable $mailable): void
    {
        $this->emailDeliveryService->sendPatientMail(
            $patient->clinic,
            $patient,
            $mailable,
            'patient_notification',
            $patient->id
        );
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

    private function freeCancelUntilLocal(Appointment $appointment, string $timezone): ?string
    {
        if (! $appointment->slot_datetime) {
            return null;
        }

        $slotLocal = CarbonImmutable::parse($appointment->slot_datetime)->setTimezone($timezone);
        $bookedAt = $appointment->created_at ? CarbonImmutable::parse($appointment->created_at)->setTimezone($timezone) : now($timezone);
        $standardDeadline = $slotLocal->subHours(24);
        $minimumWindow = $bookedAt->addHours(2);
        $deadline = $standardDeadline->greaterThan($minimumWindow) ? $standardDeadline : $minimumWindow;

        if ($deadline->greaterThan($slotLocal)) {
            $deadline = $slotLocal;
        }

        return $deadline->format('Y-m-d H:i');
    }

    private function depositRequired(?\App\Models\AppointmentType $appointmentType): bool
    {
        if (! $appointmentType) {
            return false;
        }

        if ($appointmentType->is_medical) {
            return false;
        }

        $amount = (int) ($appointmentType->deposit_amount_cents ?? 0);

        return $amount >= 50;
    }
}
