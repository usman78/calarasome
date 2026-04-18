<p>Hello {{ $details['patient'] ?? 'Patient' }},</p>

<p>Your appointment at {{ $details['clinic'] ?? 'the clinic' }} was marked as a no-show in error.</p>

@if (($details['refund_note'] ?? null))
    <p>{{ $details['refund_note'] }}</p>
@endif

@if (($details['slot_local'] ?? null))
    <p><strong>Appointment time:</strong> {{ $details['slot_local'] }} ({{ $details['timezone'] ?? 'UTC' }})</p>
@endif

<p>We apologize for the confusion.</p>
