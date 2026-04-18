<?php

namespace App\Services;

use App\Models\Clinic;
use App\Models\EmailDeliveryLog;
use App\Models\Patient;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailDeliveryService
{
    public function sendPatientMail(
        ?Clinic $clinic,
        ?Patient $patient,
        Mailable $mailable,
        string $contextType,
        ?int $contextId = null,
        array $meta = [],
        bool $throwOnFailure = false,
    ): ?EmailDeliveryLog {
        if (! $patient || ! $patient->email) {
            return $this->logSkipped(
                $clinic,
                $patient,
                $patient?->email,
                $mailable,
                'missing_email',
                $contextType,
                $contextId,
                $meta
            );
        }

        $consent = $patient->communication_consent ?? [];
        if (! (bool) ($consent['emailConsent'] ?? false)) {
            return $this->logSkipped(
                $clinic,
                $patient,
                $patient->email,
                $mailable,
                'missing_consent',
                $contextType,
                $contextId,
                $meta
            );
        }

        return $this->sendToAddress(
            $clinic,
            $patient,
            $patient->email,
            $mailable,
            $contextType,
            $contextId,
            $meta,
            $throwOnFailure
        );
    }

    public function sendToAddress(
        ?Clinic $clinic,
        ?Patient $patient,
        string $recipientEmail,
        Mailable $mailable,
        string $contextType,
        ?int $contextId = null,
        array $meta = [],
        bool $throwOnFailure = false,
    ): ?EmailDeliveryLog {
        $recipientEmail = trim(strtolower($recipientEmail));
        if ($recipientEmail === '') {
            return $this->logSkipped(
                $clinic,
                $patient,
                null,
                $mailable,
                'missing_email',
                $contextType,
                $contextId,
                $meta
            );
        }

        $log = EmailDeliveryLog::query()->create([
            'clinic_id' => $clinic?->id,
            'patient_id' => $patient?->id,
            'context_type' => $contextType,
            'context_id' => $contextId,
            'mailable' => get_class($mailable),
            'recipient_email' => $recipientEmail,
            'status' => 'pending',
            'meta' => $meta,
        ]);

        try {
            Mail::to($recipientEmail)->send($mailable);

            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return $log->fresh();
        } catch (Throwable $exception) {
            [$reason, $action] = $this->classifyFailure($exception->getMessage());

            $log->update([
                'status' => 'failed',
                'failure_reason' => $reason,
                'failure_class' => get_class($exception),
                'failure_message' => $exception->getMessage(),
                'suggested_action' => $action,
                'failed_at' => now(),
            ]);

            if ($throwOnFailure) {
                throw $exception;
            }

            return $log->fresh();
        }
    }

    public function logSkipped(
        ?Clinic $clinic,
        ?Patient $patient,
        ?string $recipientEmail,
        Mailable|string $mailable,
        string $reason,
        string $contextType,
        ?int $contextId = null,
        array $meta = [],
    ): EmailDeliveryLog {
        return EmailDeliveryLog::query()->create([
            'clinic_id' => $clinic?->id,
            'patient_id' => $patient?->id,
            'context_type' => $contextType,
            'context_id' => $contextId,
            'mailable' => is_string($mailable) ? $mailable : get_class($mailable),
            'recipient_email' => $recipientEmail,
            'status' => 'skipped',
            'failure_reason' => $reason,
            'suggested_action' => $this->suggestedActionForReason($reason),
            'meta' => $meta,
        ]);
    }

    /** @return array{0:string,1:string} */
    private function classifyFailure(string $message): array
    {
        $normalized = strtolower($message);

        if (str_contains($normalized, 'invalid') || str_contains($normalized, 'address') || str_contains($normalized, 'recipient')) {
            return ['invalid_address', $this->suggestedActionForReason('invalid_address')];
        }

        if (str_contains($normalized, 'auth') || str_contains($normalized, 'credential') || str_contains($normalized, 'username') || str_contains($normalized, 'password')) {
            return ['authentication_failed', $this->suggestedActionForReason('authentication_failed')];
        }

        if (str_contains($normalized, 'timeout') || str_contains($normalized, 'timed out')) {
            return ['timeout', $this->suggestedActionForReason('timeout')];
        }

        if (str_contains($normalized, 'connection') || str_contains($normalized, 'socket') || str_contains($normalized, 'transport') || str_contains($normalized, 'stream')) {
            return ['transport_connection', $this->suggestedActionForReason('transport_connection')];
        }

        return ['delivery_failed', $this->suggestedActionForReason('delivery_failed')];
    }

    private function suggestedActionForReason(string $reason): string
    {
        return match ($reason) {
            'missing_email' => 'Confirm the patient email address, then resend the message.',
            'missing_consent' => 'Check consent settings before sending reminders or secure links.',
            'invalid_address' => 'Confirm the spelling of the email address with the patient and resend.',
            'authentication_failed' => 'Review mail provider credentials or API keys, then retry.',
            'timeout', 'transport_connection' => 'Check mail server connectivity or provider status, then retry.',
            default => 'Review the mail configuration and retry the message manually.',
        };
    }
}
