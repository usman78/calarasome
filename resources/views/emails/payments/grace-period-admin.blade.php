<p>Admin alert:</p>

<p>Deposit authorization failed for a patient. A 48-hour grace period has started.</p>

<ul>
    <li><strong>Clinic:</strong> {{ $details['clinic'] }}</li>
    <li><strong>Patient:</strong> {{ $details['patient'] }}</li>
    <li><strong>Email:</strong> {{ $details['email'] }}</li>
    <li><strong>Appointment time:</strong> {{ $details['slot_local'] }} ({{ $details['timezone'] }})</li>
    <li><strong>Grace expires:</strong> {{ $details['grace_expires_at'] }}</li>
</ul>
