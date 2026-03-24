<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AppointmentCancellationClinicEmail extends Mailable
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
            ->subject($this->details['subject'] ?? 'Appointment Cancellation Notice')
            ->view('emails.appointments.clinic-cancellation');
    }
}
