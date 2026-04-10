<p>Hello,</p>

<p>Your appointment details are available through a secure link. For your privacy, we do not include sensitive information in this email.</p>

<p>
    Secure link: <a href="{{ $secureUrl }}">{{ $secureUrl }}</a>
</p>

<p>This link expires at {{ $expiresAt->format('Y-m-d H:i') }}.</p>

@if (! empty($freeCancelUntil) && ! empty($depositRequired))
    <p>You can cancel for free until {{ $freeCancelUntil }}. After that, your deposit will be retained.</p>
@endif
@if (! empty($cancellationNotFree))
    <p>Because this appointment is within 24 hours, cancellations will result in your deposit being retained per our policy.</p>
@endif

<p>If you did not request this, please ignore this email.</p>
