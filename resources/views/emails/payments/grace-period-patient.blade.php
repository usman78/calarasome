<p>Hello,</p>

<p>We were unable to authorize the deposit for your appointment at {{ $details['clinic'] }}.</p>

<p>You have a 48-hour grace period to update your payment method. If payment is not resolved by {{ $details['grace_expires_at'] }}, your appointment will be canceled.</p>

<p><strong>Appointment time:</strong> {{ $details['slot_local'] }} ({{ $details['timezone'] }})</p>

<p>Please contact the clinic to update your payment details.</p>
