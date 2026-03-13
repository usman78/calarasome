<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentGracePeriodAdminEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /** @var array{clinic:string,patient:string,email:string,slot_local:string,timezone:string,grace_expires_at:string} */
    public array $details;

    /** @param array{clinic:string,patient:string,email:string,slot_local:string,timezone:string,grace_expires_at:string} $details */
    public function __construct(array $details)
    {
        $this->details = $details;
    }

    public function build(): self
    {
        return $this->subject('Payment authorization failed - 48h grace started')
            ->view('emails.payments.grace-period-admin');
    }
}
