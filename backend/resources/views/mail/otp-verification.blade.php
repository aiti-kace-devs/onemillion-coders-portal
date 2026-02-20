<x-mail::message>
# Verify Your Email

We received a request to verify your email address **{{ $recipientEmail }}** for programme registration.

<x-mail::panel>
<div style="text-align: center;">
<p style="margin: 0 0 8px 0; font-size: 13px; color: #888; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600;">
Your Verification Code
</p>
<p style="margin: 0; font-size: 36px; font-weight: 800; color: #2d3748; letter-spacing: 8px; font-family: 'Courier New', Courier, monospace;">
{{ $otpCode }}
</p>
<p style="margin: 10px 0 0 0; font-size: 13px; color: #999;">
Expires in <strong>{{ $expiresInMinutes }} minutes</strong>
</p>
</div>
</x-mail::panel>

Enter this code in the registration form to verify your email address.

---

<small>If you did not request this code, you can safely ignore this email. Never share your verification code with anyone.</small>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
