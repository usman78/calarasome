<?php

namespace App\Services;

use App\Mail\EmailVerificationCodeEmail;
use App\Models\Clinic;
use App\Models\EmailVerificationCode;

class EmailVerificationService
{
    public function __construct(
        private readonly EmailDeliveryService $emailDeliveryService,
    ) {
    }

    /** @return array{sent:bool,message:string} */
    public function sendBookingCode(Clinic $clinic, string $email): array
    {
        $normalizedEmail = $this->normalizeEmail($email);

        EmailVerificationCode::query()
            ->where('clinic_id', $clinic->id)
            ->where('email', $normalizedEmail)
            ->whereNull('verified_at')
            ->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $record = EmailVerificationCode::query()->create([
            'clinic_id' => $clinic->id,
            'email' => $normalizedEmail,
            'code_hash' => hash('sha256', $code),
            'attempts' => 0,
            'sent_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'meta' => ['purpose' => 'booking_verification'],
        ]);

        $log = $this->emailDeliveryService->sendToAddress(
            $clinic,
            null,
            $normalizedEmail,
            new EmailVerificationCodeEmail($clinic->name ?? 'Clinic', $code, $record->expires_at),
            'email_verification',
            $record->id,
            ['purpose' => 'booking_verification']
        );

        if (! $log || $log->status !== 'sent') {
            $record->delete();

            return [
                'sent' => false,
                'message' => 'We could not send the verification code. Please confirm the email address and try again.',
            ];
        }

        return [
            'sent' => true,
            'message' => 'Verification code sent. Check your email and enter the 6-digit code below.',
        ];
    }

    public function verifyBookingCode(Clinic $clinic, string $email, string $code): bool
    {
        $normalizedEmail = $this->normalizeEmail($email);

        $record = EmailVerificationCode::query()
            ->where('clinic_id', $clinic->id)
            ->where('email', $normalizedEmail)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $record || $record->attempts >= 5) {
            return false;
        }

        $record->increment('attempts');

        if (! hash_equals($record->code_hash, hash('sha256', trim($code)))) {
            return false;
        }

        $record->update(['verified_at' => now()]);

        return true;
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}
