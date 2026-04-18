<?php

namespace App\Services;

use App\Mail\PaymentGracePeriodAdminEmail;
use App\Mail\PaymentGracePeriodPatientEmail;
use App\Models\Appointment;
use App\Models\AppointmentPayment;
use App\Models\AppointmentType;
use App\Models\AuditLog;
use App\Models\Patient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class AppointmentPaymentService
{
    public const NO_SHOW_REVERSAL_WINDOW_MINUTES = 30;

    public function __construct(
        private readonly StripeGateway $stripeGateway,
        private readonly AppointmentCommunicationService $communicationService,
        private readonly EmailDeliveryService $emailDeliveryService,
    ) {
    }

    /** @return array{strategy:string,status:string,client_secret:?string} */
    public function initializePayment(Appointment $appointment, AppointmentType $appointmentType, Patient $patient): array
    {
        if (! config('services.stripe.secret')) {
            AppointmentPayment::query()->create([
                'appointment_id' => $appointment->id,
                'patient_id' => $patient->id,
                'appointment_type_id' => $appointmentType->id,
                'strategy' => 'skip',
                'status' => 'skipped_unconfigured',
                'amount_cents' => (int) ($appointmentType->deposit_amount_cents ?? 0),
                'currency' => $appointmentType->deposit_currency ?: 'usd',
            ]);

            return [
                'strategy' => 'skip',
                'status' => 'skipped_unconfigured',
                'client_secret' => null,
            ];
        }

        $amount = (int) ($appointmentType->deposit_amount_cents ?? 0);
        $currency = strtolower($appointmentType->deposit_currency ?: 'usd');
        $minimum = $this->minimumChargeAmount($currency);

        if ($appointmentType->is_medical || $amount <= 0 || $amount < $minimum) {
            AppointmentPayment::query()->create([
                'appointment_id' => $appointment->id,
                'patient_id' => $patient->id,
                'appointment_type_id' => $appointmentType->id,
                'strategy' => 'skip',
                'status' => $amount < $minimum ? 'skipped_minimum' : 'skipped',
                'amount_cents' => $amount,
                'currency' => $currency,
            ]);

            return [
                'strategy' => 'skip',
                'status' => $amount < $minimum ? 'skipped_minimum' : 'skipped',
                'client_secret' => null,
            ];
        }

        $daysAway = now()->diffInDays(CarbonImmutable::parse($appointment->slot_datetime), false);

        if ($daysAway <= 7) {
            $intent = $this->stripeGateway->createPaymentIntent([
                'amount' => $amount,
                'currency' => $currency,
                'capture_method' => 'manual',
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => [
                    'appointment_id' => $appointment->id,
                    'patient_id' => $patient->id,
                ],
            ]);

            AppointmentPayment::query()->create([
                'appointment_id' => $appointment->id,
                'patient_id' => $patient->id,
                'appointment_type_id' => $appointmentType->id,
                'strategy' => 'payment_intent',
                'status' => $intent['status'] ?? 'requires_payment_method',
                'amount_cents' => $amount,
                'currency' => $currency,
                'stripe_payment_intent_id' => $intent['id'] ?? null,
                'auth_scheduled_for' => null,
            ]);

            return [
                'strategy' => 'payment_intent',
                'status' => $intent['status'] ?? 'requires_payment_method',
                'client_secret' => $intent['client_secret'] ?? null,
            ];
        }

        $setupIntent = $this->stripeGateway->createSetupIntent([
            'usage' => 'off_session',
            'metadata' => [
                'appointment_id' => $appointment->id,
                'patient_id' => $patient->id,
            ],
        ]);

        $scheduledFor = CarbonImmutable::parse($appointment->slot_datetime)->subDays(7);

        AppointmentPayment::query()->create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'appointment_type_id' => $appointmentType->id,
            'strategy' => 'setup_intent',
            'status' => $setupIntent['status'] ?? 'requires_payment_method',
            'amount_cents' => $amount,
            'currency' => $currency,
            'stripe_setup_intent_id' => $setupIntent['id'] ?? null,
            'auth_scheduled_for' => $scheduledFor,
        ]);

        return [
            'strategy' => 'setup_intent',
            'status' => $setupIntent['status'] ?? 'requires_payment_method',
            'client_secret' => $setupIntent['client_secret'] ?? null,
        ];
    }

    public function recordSetupIntentSucceeded(string $setupIntentId, ?string $paymentMethodId): void
    {
        if (! $paymentMethodId) {
            return;
        }

        $payment = AppointmentPayment::query()
            ->where('stripe_setup_intent_id', $setupIntentId)
            ->first();

        if (! $payment) {
            return;
        }

        $payment->update([
            'stripe_payment_method_id' => $paymentMethodId,
            'status' => 'pending_setup',
        ]);
    }

    public function recordPaymentIntentStatus(string $paymentIntentId, ?string $status): void
    {
        if (! $paymentIntentId || ! $status) {
            return;
        }

        $payment = AppointmentPayment::query()
            ->where('stripe_payment_intent_id', $paymentIntentId)
            ->first();

        if (! $payment) {
            return;
        }

        $updates = ['status' => $status];

        if ($status === 'requires_capture' && ! $payment->authorized_at) {
            $updates['authorized_at'] = now();
        }

        if ($status === 'succeeded' && ! $payment->captured_at) {
            $updates['captured_at'] = now();
        }

        if ($status === 'failed' && ! $payment->failed_at) {
            $updates['failed_at'] = now();
        }

        if ($status === 'canceled') {
            if ($payment->status === 'voided' || $payment->voided_at) {
                $updates['status'] = 'voided';
                $updates['voided_at'] = $payment->voided_at ?? now();
            } elseif (! $payment->failed_at) {
                $updates['failed_at'] = now();
            }
        }

        $payment->update($updates);

        if (in_array($status, ['failed', 'canceled'], true) && ($updates['status'] ?? $status) !== 'voided') {
            $this->startGracePeriod($payment);
        }

        if (in_array($status, ['requires_capture', 'succeeded'], true)) {
            $payment->update([
                'grace_started_at' => null,
                'grace_expires_at' => null,
            ]);
        }
    }

    public function recordChargeRefunded(string $paymentIntentId, ?string $chargeId): void
    {
        if (! $paymentIntentId) {
            return;
        }

        $payment = AppointmentPayment::query()
            ->where('stripe_payment_intent_id', $paymentIntentId)
            ->first();

        if (! $payment) {
            return;
        }

        $payment->update([
            'status' => 'refunded',
            'failed_at' => $payment->failed_at ?? now(),
            'refunded_at' => $payment->refunded_at ?? now(),
            'refund_id' => $payment->refund_id ?? $chargeId,
        ]);
    }

    public function recordChargeDispute(string $paymentIntentId, ?string $chargeId): void
    {
        if (! $paymentIntentId) {
            return;
        }

        $payment = AppointmentPayment::query()
            ->where('stripe_payment_intent_id', $paymentIntentId)
            ->first();

        if (! $payment) {
            return;
        }

        $payment->update([
            'status' => 'disputed',
            'failed_at' => $payment->failed_at ?? now(),
        ]);
    }

    public function cancelExpiredGrace(): int
    {
        $expired = AppointmentPayment::query()
            ->whereIn('status', ['failed', 'canceled'])
            ->whereNotNull('grace_expires_at')
            ->where('grace_expires_at', '<=', now())
            ->get();

        $count = 0;

        foreach ($expired as $payment) {
            $appointment = $payment->appointment;

            if ($appointment && $appointment->status !== 'cancelled_by_clinic') {
                $appointment->update(['status' => 'cancelled_by_clinic']);
                app(WaitlistNotificationService::class)->createFromAppointment($appointment);
            }

            $payment->update(['status' => 'grace_expired']);
            $count++;
        }

        return $count;
    }

    /** @return array{appointment_status:string,payment_action:string,policy?:string} */
    public function cancelByPatient(Appointment $appointment): array
    {
        $appointment->loadMissing(['payment', 'patient', 'clinic']);

        if (in_array($appointment->status, ['cancelled_by_patient', 'cancelled_by_clinic', 'no_show'], true)) {
            return [
                'appointment_status' => $appointment->status,
                'payment_action' => 'none',
            ];
        }

        if ($appointment->slot_datetime && $appointment->slot_datetime->isPast()) {
            throw new RuntimeException('Appointment has already passed.');
        }

        $deadline = $this->freeCancelDeadline($appointment);
        $isLate = $deadline ? now()->greaterThan($deadline) : false;
        $paymentAction = 'none';
        $policy = $isLate ? 'deposit_retained' : 'no_charge';

        $payment = $appointment->payment;
        if ($payment && $payment->amount_cents > 0 && $payment->strategy !== 'skip') {
            if ($payment->status === 'refunded') {
                $paymentAction = 'refunded';
                $policy = 'refund_issued';
            } elseif ($payment->stripe_payment_intent_id) {
                if ($isLate) {
                    $paymentAction = $this->capturePayment($payment);
                    $policy = 'deposit_retained';
                } else {
                    if ($this->isCaptured($payment)) {
                        $paymentAction = $this->refundPayment($payment);
                        $policy = 'refund_issued';
                    } else {
                        $paymentAction = $this->voidPayment($payment);
                    }
                }
            } else {
                $paymentAction = $this->voidPayment($payment);
            }
        }

        $appointment->update(['status' => 'cancelled_by_patient']);

        if ($appointment->patient) {
            $this->communicationService->sendPatientCancellationEmail(
                $appointment,
                $appointment->patient,
                $policy
            );
        }

        app(WaitlistNotificationService::class)->createFromAppointment($appointment);

        return [
            'appointment_status' => 'cancelled_by_patient',
            'payment_action' => $paymentAction,
            'policy' => $policy,
        ];
    }

    /** @return array{appointment_status:string,payment_action:string} */
    public function cancelByClinic(Appointment $appointment): array
    {
        $appointment->loadMissing(['payment', 'patient', 'clinic']);

        if (in_array($appointment->status, ['cancelled_by_patient', 'cancelled_by_clinic'], true)) {
            return [
                'appointment_status' => $appointment->status,
                'payment_action' => 'none',
            ];
        }

        $paymentAction = 'none';
        $refunded = false;

        $payment = $appointment->payment;
        if ($payment && $payment->amount_cents > 0 && $payment->strategy !== 'skip') {
            if ($payment->status === 'refunded') {
                $paymentAction = 'refunded';
                $refunded = true;
            } elseif ($payment->stripe_payment_intent_id) {
                if ($this->isCaptured($payment)) {
                    $paymentAction = $this->refundPayment($payment);
                    $refunded = true;
                } else {
                    $paymentAction = $this->voidPayment($payment);
                }
            } else {
                $paymentAction = $this->voidPayment($payment);
            }
        }

        $appointment->update(['status' => 'cancelled_by_clinic']);

        if ($appointment->patient) {
            $this->communicationService->sendClinicCancellationEmail(
                $appointment,
                $appointment->patient,
                $refunded
            );
        }

        app(WaitlistNotificationService::class)->createFromAppointment($appointment);

        return [
            'appointment_status' => 'cancelled_by_clinic',
            'payment_action' => $paymentAction,
        ];
    }

    /** @return array{appointment_status:string,payment_action:string} */
    public function markNoShow(Appointment $appointment, bool $chargeDeposit): array
    {
        $appointment->loadMissing(['payment', 'patient', 'clinic']);

        if (in_array($appointment->status, ['cancelled_by_patient', 'cancelled_by_clinic'], true)) {
            throw new RuntimeException('Cannot mark a cancelled appointment as no-show.');
        }

        if ($appointment->status === 'no_show') {
            return [
                'appointment_status' => 'no_show',
                'payment_action' => 'none',
            ];
        }

        if ($appointment->slot_datetime && $appointment->slot_datetime->isFuture()) {
            throw new RuntimeException('Cannot mark a future appointment as no-show.');
        }

        $paymentAction = 'none';
        $payment = $appointment->payment;

        if ($chargeDeposit && $payment && $payment->amount_cents > 0 && $payment->strategy !== 'skip') {
            if (! $payment->stripe_payment_intent_id) {
                throw new RuntimeException('No payment intent available to capture.');
            }

            if (! $this->isCaptured($payment)) {
                $paymentAction = $this->capturePayment($payment);
            } else {
                $paymentAction = 'captured';
            }
        }

        $markedAt = now();
        $appointment->update([
            'status' => 'no_show',
            'no_show_previous_status' => $appointment->status,
            'no_show_marked_at' => $markedAt,
            'no_show_reversible_until' => $markedAt->copy()->addMinutes(self::NO_SHOW_REVERSAL_WINDOW_MINUTES),
            'no_show_reversed_at' => null,
        ]);

        if ($appointment->patient) {
            $appointment->patient->increment('no_show_count');
            $this->communicationService->sendNoShowEmail($appointment, $appointment->patient, $payment?->amount_cents ?? 0);
        }

        return [
            'appointment_status' => 'no_show',
            'payment_action' => $paymentAction,
        ];
    }

    /** @return array{appointment_status:string,payment_action:string,window_mode:string} */
    public function reverseNoShow(
        Appointment $appointment,
        int $adminId,
        ?string $reason = null,
        ?string $notes = null,
    ): array {
        $appointment->loadMissing(['payment', 'patient', 'clinic']);

        if ($appointment->status !== 'no_show') {
            throw new RuntimeException('Only no-show appointments can be reversed.');
        }

        $withinWindow = $this->canUndoNoShowNow($appointment);
        if (! $withinWindow && ! filled($reason)) {
            throw new RuntimeException('A reason is required once the no-show undo window has closed.');
        }

        $reason = $withinWindow ? 'marked_in_error' : (string) $reason;
        $paymentAction = 'none';
        $refunded = false;
        $windowMode = $withinWindow ? 'undo_window' : 'post_window';

        DB::transaction(function () use ($appointment, $adminId, $reason, $notes, $windowMode, &$paymentAction, &$refunded): void {
            $appointment->refresh();
            $appointment->loadMissing(['payment', 'patient', 'clinic']);

            if ($appointment->status !== 'no_show') {
                throw new RuntimeException('This appointment is no longer marked as no-show.');
            }

            $payment = $appointment->payment;
            if ($payment && $payment->amount_cents > 0 && $payment->strategy !== 'skip') {
                if ($payment->status === 'refunded') {
                    $paymentAction = 'refunded';
                    $refunded = true;
                } elseif ($this->isCaptured($payment)) {
                    $paymentAction = $this->refundPayment($payment);
                    $refunded = true;
                }
            }

            $appointment->update([
                'status' => $appointment->no_show_previous_status ?: 'completed',
                'no_show_reversed_at' => now(),
                'no_show_reversible_until' => null,
            ]);

            if ($appointment->patient && $appointment->patient->no_show_count > 0) {
                $appointment->patient->decrement('no_show_count');
            }

            AuditLog::query()->create([
                'user_id' => $adminId,
                'clinic_id' => $appointment->clinic_id,
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'action' => 'reverse_no_show',
                'reason' => $reason,
                'notes' => $notes,
                'meta' => [
                    'window_mode' => $windowMode,
                    'payment_action' => $paymentAction,
                    'previous_status' => $appointment->no_show_previous_status,
                ],
            ]);
        });

        $appointment = $appointment->fresh(['patient', 'clinic', 'payment']);

        if ($appointment?->patient) {
            $this->communicationService->sendNoShowReversedEmail($appointment, $appointment->patient, $refunded);
        }

        return [
            'appointment_status' => $appointment?->status ?? 'completed',
            'payment_action' => $paymentAction,
            'window_mode' => $windowMode,
        ];
    }

    public function authorizeScheduledHolds(): int
    {
        $eligible = AppointmentPayment::query()
            ->where('strategy', 'setup_intent')
            ->where('status', 'pending_setup')
            ->whereNotNull('stripe_payment_method_id')
            ->whereNull('stripe_payment_intent_id')
            ->whereNotNull('auth_scheduled_for')
            ->where('auth_scheduled_for', '<=', now())
            ->get();

        $count = 0;

        foreach ($eligible as $payment) {
            try {
                $intent = $this->stripeGateway->createPaymentIntent([
                    'amount' => $payment->amount_cents,
                    'currency' => $payment->currency,
                    'capture_method' => 'manual',
                    'payment_method' => $payment->stripe_payment_method_id,
                    'confirm' => true,
                    'off_session' => true,
                    'metadata' => [
                        'appointment_id' => $payment->appointment_id,
                        'patient_id' => $payment->patient_id,
                        'origin' => 't7_scheduler',
                    ],
                ]);

                $status = $intent['status'] ?? 'requires_capture';

                $payment->update([
                    'stripe_payment_intent_id' => $intent['id'] ?? null,
                    'status' => $status,
                    'authorized_at' => in_array($status, ['requires_capture', 'succeeded'], true) ? now() : null,
                ]);

                if (! in_array($status, ['requires_capture', 'succeeded'], true)) {
                    $this->startGracePeriod($payment);
                }
            } catch (Throwable) {
                $this->startGracePeriod($payment);
            }

            $count++;
        }

        return $count;
    }

    private function startGracePeriod(AppointmentPayment $payment): void
    {
        if ($payment->strategy !== 'setup_intent') {
            return;
        }

        if ($payment->grace_started_at) {
            return;
        }

        $payment->update([
            'status' => $payment->status === 'canceled' ? 'canceled' : 'failed',
            'failed_at' => $payment->failed_at ?? now(),
            'grace_started_at' => now(),
            'grace_expires_at' => now()->addHours(48),
        ]);

        $this->notifyGraceStarted($payment);
    }

    private function notifyGraceStarted(AppointmentPayment $payment): void
    {
        $appointment = $payment->appointment()
            ->with(['clinic:id,name,timezone', 'provider:id,full_name'])
            ->first();
        $patient = $payment->patient;

        if (! $appointment || ! $patient || ! $patient->email) {
            return;
        }

        $timezone = $appointment->clinic?->timezone ?? 'UTC';
        $slotLocal = CarbonImmutable::parse($appointment->slot_datetime)
            ->setTimezone($timezone)
            ->format('Y-m-d H:i:s');

        $details = [
            'clinic' => $appointment->clinic?->name ?? 'Clinic',
            'slot_local' => $slotLocal,
            'timezone' => $timezone,
            'grace_expires_at' => $payment->grace_expires_at?->format('Y-m-d H:i:s') ?? now()->addHours(48)->format('Y-m-d H:i:s'),
        ];

        $this->emailDeliveryService->sendPatientMail(
            $appointment->clinic,
            $patient,
            new PaymentGracePeriodPatientEmail($details),
            'appointment_payment',
            $payment->id,
            ['kind' => 'payment_grace_started']
        );

        $adminEmail = config('services.payment_alerts.admin_email');
        if ($adminEmail) {
            $this->emailDeliveryService->sendToAddress(
                $appointment->clinic,
                $patient,
                $adminEmail,
                new PaymentGracePeriodAdminEmail([
                    'clinic' => $details['clinic'],
                    'patient' => $patient->full_name ?? 'Patient',
                    'email' => $patient->email,
                    'slot_local' => $details['slot_local'],
                    'timezone' => $details['timezone'],
                    'grace_expires_at' => $details['grace_expires_at'],
                ]),
                'appointment_payment',
                $payment->id,
                ['kind' => 'payment_grace_admin_alert']
            );
        }
    }

    private function isCaptured(AppointmentPayment $payment): bool
    {
        return (bool) ($payment->captured_at) || in_array($payment->status, ['succeeded', 'captured'], true);
    }

    private function voidPayment(AppointmentPayment $payment): string
    {
        if ($payment->status === 'voided') {
            return 'voided';
        }

        if (
            $payment->stripe_payment_intent_id
            && ! in_array($payment->status, ['failed', 'canceled', 'refunded', 'disputed', 'grace_expired'], true)
        ) {
            $this->stripeGateway->cancelPaymentIntent($payment->stripe_payment_intent_id);
        }

        $payment->update([
            'status' => 'voided',
            'voided_at' => $payment->voided_at ?? now(),
            'auth_scheduled_for' => null,
        ]);

        return 'voided';
    }

    private function capturePayment(AppointmentPayment $payment): string
    {
        if ($this->isCaptured($payment)) {
            return 'captured';
        }

        if (! $payment->stripe_payment_intent_id) {
            throw new RuntimeException('Unable to capture payment without payment intent.');
        }

        $this->stripeGateway->capturePaymentIntent($payment->stripe_payment_intent_id);

        $payment->update([
            'status' => 'succeeded',
            'captured_at' => $payment->captured_at ?? now(),
        ]);

        return 'captured';
    }

    private function refundPayment(AppointmentPayment $payment): string
    {
        if ($payment->status === 'refunded') {
            return 'refunded';
        }

        if (! $payment->stripe_payment_intent_id) {
            throw new RuntimeException('Unable to refund without payment intent.');
        }

        $refund = $this->stripeGateway->createRefund([
            'payment_intent' => $payment->stripe_payment_intent_id,
        ]);

        $payment->update([
            'status' => 'refunded',
            'refunded_at' => $payment->refunded_at ?? now(),
            'refund_id' => $refund['id'] ?? $payment->refund_id,
        ]);

        return 'refunded';
    }

    private function minimumChargeAmount(string $currency): int
    {
        return match (strtolower($currency)) {
            'usd' => 50,
            default => 50,
        };
    }

    public function freeCancelDeadline(Appointment $appointment): ?CarbonImmutable
    {
        if (! $appointment->slot_datetime) {
            return null;
        }

        $slot = CarbonImmutable::parse($appointment->slot_datetime);
        $bookedAt = $appointment->created_at
            ? CarbonImmutable::parse($appointment->created_at)
            : now();

        $standardDeadline = $slot->subHours(24);
        $minimumWindow = $bookedAt->addHours(2);
        $deadline = $standardDeadline->greaterThan($minimumWindow) ? $standardDeadline : $minimumWindow;

        if ($deadline->greaterThan($slot)) {
            $deadline = $slot;
        }

        return $deadline;
    }

    public function canUndoNoShowNow(Appointment $appointment): bool
    {
        if ($appointment->status !== 'no_show') {
            return false;
        }

        if (! $appointment->no_show_reversible_until) {
            return false;
        }

        return $appointment->no_show_reversible_until->greaterThan(now());
    }
}
