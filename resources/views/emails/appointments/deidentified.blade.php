<p>Hello,</p>

<p>Your appointment details are available through a secure link. For your privacy, we do not include sensitive information in this email.</p>

<p>
    Secure link: <a href="{{ $secureUrl }}">{{ $secureUrl }}</a>
</p>

<p>This link expires at {{ $expiresAt->format('Y-m-d H:i') }}.</p>

<p>If you did not request this, please ignore this email.</p>
