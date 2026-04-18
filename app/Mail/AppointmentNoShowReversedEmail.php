<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AppointmentNoShowReversedEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /** @param array<string, mixed> $details */
    public function __construct(
        public readonly array $details,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('No-show corrected')
            ->view('emails.appointments.no-show-reversed');
    }
}
