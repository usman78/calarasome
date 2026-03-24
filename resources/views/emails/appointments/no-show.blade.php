<p>Hello {{ $details['patient'] ?? 'there' }},</p>

<p>Our records show that you missed your appointment at {{ $details['clinic'] ?? 'our clinic' }}.</p>

@if (! empty($details['slot_local']))
    <p>Appointment time: {{ $details['slot_local'] }} ({{ $details['timezone'] ?? 'local time' }}).</p>
@endif

@if (! empty($details['amount']))
    <p>Your deposit of {{ $details['amount'] }} has been charged.</p>
@endif

<p>If you believe this is an error, please contact the clinic.</p>
