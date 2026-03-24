<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AppointmentCancellationPatientEmail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<string, mixed> $details */
    public function __construct(
        public array $details,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject($this->details['subject'] ?? 'Appointment Cancellation')
            ->view('emails.appointments.patient-cancellation');
    }
}
