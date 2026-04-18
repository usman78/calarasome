<?php

namespace App\Mail;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerificationCodeEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $clinicName,
        public readonly string $code,
        public readonly CarbonInterface $expiresAt,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Verify your email address')
            ->view('emails.verification.code');
    }
}
