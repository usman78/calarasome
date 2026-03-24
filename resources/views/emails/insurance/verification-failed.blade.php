<p>Hello {{ $details['patient'] ?? 'Patient' }},</p>

<p>We were unable to verify your insurance information for your upcoming visit.</p>

<p>Please contact {{ $details['clinic'] ?? 'the clinic' }} to provide updated insurance details so we can confirm your appointment.</p>

<p>If you believe this is an error, please reply to this email or call the clinic directly.</p>
