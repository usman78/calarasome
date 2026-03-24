<p>Hello {{ $details['patient'] ?? 'there' }},</p>

<p>Your appointment at {{ $details['clinic'] ?? 'our clinic' }} has been canceled.</p>

@if (($details['policy'] ?? '') === 'deposit_retained')
    <p>Your deposit has been retained per our cancellation policy.</p>
@elseif (($details['policy'] ?? '') === 'refund_issued')
    <p>Your deposit has been refunded. You should see the credit on your statement shortly.</p>
@else
    <p>No charge was made, and any authorization hold has been released.</p>
@endif

@if (! empty($details['slot_local']))
    <p>Original appointment time: {{ $details['slot_local'] }} ({{ $details['timezone'] ?? 'local time' }}).</p>
@endif

<p>If you need to reschedule, please contact the clinic.</p>
