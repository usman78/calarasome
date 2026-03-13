<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentGracePeriodPatientEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /** @var array{clinic:string,slot_local:string,timezone:string,grace_expires_at:string} */
    public array $details;

    /** @param array{clinic:string,slot_local:string,timezone:string,grace_expires_at:string} $details */
    public function __construct(array $details)
    {
        $this->details = $details;
    }

    public function build(): self
    {
        return $this->subject('Payment authorization failed - action required')
            ->view('emails.payments.grace-period-patient');
    }
}
