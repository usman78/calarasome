<p>You have been added to the waitlist.</p>
<p><strong>Clinic:</strong> {{ $clinicName }}</p>
<p><strong>Treatment:</strong> {{ $appointmentType }}</p>
@if ($preferredDate)
    <p><strong>Preferred date:</strong> {{ $preferredDate }}</p>
@endif
@if ($preferredTimeWindow)
    <p><strong>Preferred time window:</strong> {{ $preferredTimeWindow }}</p>
@endif
<p>We will notify you when a slot opens that fits your request.</p>
