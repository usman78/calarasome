<p>Hello {{ $details['patient'] ?? 'there' }},</p>

<p>{{ $details['clinic'] ?? 'Our clinic' }} has canceled your appointment.</p>

@if (! empty($details['slot_local']))
    <p>Original appointment time: {{ $details['slot_local'] }} ({{ $details['timezone'] ?? 'local time' }}).</p>
@endif

@if (! empty($details['refund_note']))
    <p>{{ $details['refund_note'] }}</p>
@else
    <p>No charge has been made for this appointment.</p>
@endif

<p>We apologize for the inconvenience. Please contact us if you would like to reschedule.</p>
