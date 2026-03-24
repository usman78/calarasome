<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InsuranceVerificationFailedEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /** @var array<string, mixed> */
    public array $details;

    /** @param array<string, mixed> $details */
    public function __construct(array $details)
    {
        $this->details = $details;
    }

    public function build(): self
    {
        return $this->subject('Insurance verification required')
            ->view('emails.insurance.verification-failed');
    }
}
