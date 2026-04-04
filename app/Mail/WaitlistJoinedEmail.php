<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WaitlistJoinedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $clinicName,
        public readonly string $appointmentType,
        public readonly ?string $preferredDate,
        public readonly ?string $preferredTimeWindow,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('You are on the waitlist')
            ->view('emails.waitlist.joined');
    }
}
