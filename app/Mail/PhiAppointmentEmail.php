<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PhiAppointmentEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /** @var array{clinic:string,provider:string,appointment_type:string,slot_local:string,timezone:string} */
    public array $details;

    /** @param array{clinic:string,provider:string,appointment_type:string,slot_local:string,timezone:string} $details */
    public function __construct(array $details)
    {
        $this->details = $details;
    }

    public function build(): self
    {
        return $this->subject('Your appointment confirmation')
            ->view('emails.appointments.phi');
    }
}
