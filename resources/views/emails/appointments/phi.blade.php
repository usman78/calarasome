<p>Hello,</p>

<p>Here are your appointment details:</p>

<ul>
    <li><strong>Clinic:</strong> {{ $details['clinic'] }}</li>
    <li><strong>Provider:</strong> {{ $details['provider'] }}</li>
    <li><strong>Treatment:</strong> {{ $details['appointment_type'] }}</li>
    <li><strong>Time:</strong> {{ $details['slot_local'] }} ({{ $details['timezone'] }})</li>
</ul>

<p>If you need to reschedule, please contact the clinic.</p>
