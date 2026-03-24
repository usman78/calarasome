<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InsuranceVerificationDailySummaryEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /** @var array{report_date:string,items:array<int,array<string,mixed>>} */
    public array $payload;

    /** @param array{report_date:string,items:array<int,array<string,mixed>>} $payload */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function build(): self
    {
        return $this->subject('Standard urgency insurance verifications due tomorrow')
            ->view('emails.insurance.daily-summary');
    }
}
