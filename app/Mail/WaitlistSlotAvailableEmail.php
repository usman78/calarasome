<?php

namespace App\Mail;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WaitlistSlotAvailableEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $secureUrl;
    public CarbonInterface $expiresAt;

    public function __construct(string $token, CarbonInterface $expiresAt)
    {
        $this->secureUrl = url('/waitlist/claim/'.$token);
        $this->expiresAt = $expiresAt;
    }

    public function build(): self
    {
        return $this->subject('A slot is available')
            ->view('emails.waitlist.slot-available');
    }
}
