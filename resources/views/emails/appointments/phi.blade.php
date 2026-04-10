<p>Hello,</p>

<p>Here are your appointment details:</p>

<ul>
    <li><strong>Clinic:</strong> {{ $details['clinic'] }}</li>
    <li><strong>Provider:</strong> {{ $details['provider'] }}</li>
    <li><strong>Treatment:</strong> {{ $details['appointment_type'] }}</li>
    <li><strong>Time:</strong> {{ $details['slot_local'] }} ({{ $details['timezone'] }})</li>
</ul>

@if (! empty($details['free_cancel_until']) && ! empty($details['deposit_required']))
    <p>You can cancel for free until {{ $details['free_cancel_until'] }} ({{ $details['timezone'] }}). After that, your deposit will be retained.</p>
@endif
@if (! empty($details['cancellation_not_free']))
    <p>Because this appointment is within 24 hours, cancellations will result in your deposit being retained per our policy.</p>
@endif

<p>If you need to reschedule, please contact the clinic.</p>
