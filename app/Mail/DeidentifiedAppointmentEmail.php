<?php

namespace App\Mail;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DeidentifiedAppointmentEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $secureUrl;
    public CarbonInterface $expiresAt;

    public function __construct(string $token, CarbonInterface $expiresAt)
    {
        $this->secureUrl = url('/appointments/secure/'.$token);
        $this->expiresAt = $expiresAt;
    }

    public function build(): self
    {
        return $this->subject('Your appointment details')
            ->view('emails.appointments.deidentified');
    }
}
