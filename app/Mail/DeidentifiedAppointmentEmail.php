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
    public ?string $freeCancelUntil;
    public bool $cancellationNotFree;
    public bool $depositRequired;

    public function __construct(string $token, CarbonInterface $expiresAt, ?string $freeCancelUntil = null, bool $cancellationNotFree = false, bool $depositRequired = false)
    {
        $this->secureUrl = url('/appointments/secure/'.$token);
        $this->expiresAt = $expiresAt;
        $this->freeCancelUntil = $freeCancelUntil;
        $this->cancellationNotFree = $cancellationNotFree;
        $this->depositRequired = $depositRequired;
    }

    public function build(): self
    {
        return $this->subject('Your appointment details')
            ->view('emails.appointments.deidentified');
    }
}
