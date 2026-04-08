<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WaitlistReengagementEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $clinicName,
        public string $appointmentType,
        public string $clinicSlug,
    ) {
    }

    public function build()
    {
        $bookingUrl = rtrim(config('app.url'), '/').'/book/'.$this->clinicSlug;

        return $this->subject('We could not secure a slot — please book the earliest available time')
            ->view('emails.waitlist.reengagement')
            ->with([
                'clinicName' => $this->clinicName,
                'appointmentType' => $this->appointmentType,
                'bookingUrl' => $bookingUrl,
            ]);
    }
}
