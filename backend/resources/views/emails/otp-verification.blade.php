<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; -webkit-font-smoothing: antialiased;">

    <!-- Wrapper -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f6f9; padding: 24px 0;">
        <tr>
            <td align="center">
                <!-- Main card -->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 520px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08);">

                    <!-- Header band -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #CE1126 0%, #FCD116 50%, #006B3F 100%); height: 6px;"></td>
                    </tr>

                    <!-- Logo / App name -->
                    <tr>
                        <td style="padding: 32px 32px 0 32px; text-align: center;">
                            <h1 style="margin: 0; font-size: 22px; font-weight: 700; color: #1a1a2e; letter-spacing: -0.5px;">
                                {{ $appName }}
                            </h1>
                        </td>
                    </tr>

                    <!-- Greeting -->
                    <tr>
                        <td style="padding: 24px 32px 0 32px; text-align: center;">
                            <p style="margin: 0; font-size: 15px; color: #555; line-height: 1.6;">
                                We received a request to verify your email address
                                <strong style="color: #1a1a2e;">{{ $recipientEmail }}</strong>
                                for programme registration.
                            </p>
                        </td>
                    </tr>

                    <!-- OTP code box -->
                    <tr>
                        <td style="padding: 28px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="background-color: #fafafa; border: 2px dashed #e0e0e0; border-radius: 12px; padding: 24px; text-align: center;">
                                        <p style="margin: 0 0 8px 0; font-size: 13px; color: #888; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600;">
                                            Your Verification Code
                                        </p>
                                        <p style="margin: 0; font-size: 40px; font-weight: 800; color: #1a1a2e; letter-spacing: 10px; font-family: 'Courier New', Courier, monospace;">
                                            {{ $otpCode }}
                                        </p>
                                        <p style="margin: 12px 0 0 0; font-size: 13px; color: #999;">
                                            Expires in <strong style="color: #CE1126;">{{ $expiresInMinutes }} minutes</strong>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Instructions — adapt based on whether a phone number is associated -->
                    <tr>
                        <td style="padding: 0 32px 8px 32px; text-align: center;">
                            @if($hasPhone)
                                <p style="margin: 0; font-size: 14px; color: #666; line-height: 1.6;">
                                    Click the button below to <strong>verify your email</strong> and receive this same code via <strong>SMS</strong> on your phone.
                                </p>
                                <p style="margin: 10px 0 0 0; font-size: 13px; color: #888; line-height: 1.5;">
                                    If your phone is unavailable, you can enter the code above directly in the registration form.
                                </p>
                            @else
                                <p style="margin: 0; font-size: 14px; color: #666; line-height: 1.6;">
                                    Enter this code in the registration form, or click the button below to verify instantly.
                                </p>
                            @endif
                        </td>
                    </tr>

                    <!-- CTA button -->
                    <tr>
                        <td style="padding: 20px 32px 28px 32px; text-align: center;">
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                                <tr>
                                    <td style="background: linear-gradient(135deg, #FCD116, #e6b800); border-radius: 10px;">
                                        <a href="{{ $verificationUrl }}"
                                           target="_blank"
                                           style="display: inline-block; padding: 14px 36px; font-size: 15px; font-weight: 700; color: #1a1a2e; text-decoration: none; letter-spacing: 0.3px;">
                                            @if($hasPhone)
                                                Verify Email &amp; Send SMS
                                            @else
                                                Verify My Email
                                            @endif
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding: 0 32px;">
                            <hr style="border: none; border-top: 1px solid #eee; margin: 0;">
                        </td>
                    </tr>

                    <!-- Security note -->
                    <tr>
                        <td style="padding: 20px 32px 28px 32px; text-align: center;">
                            <p style="margin: 0; font-size: 13px; color: #999; line-height: 1.6;">
                                If you did not request this code, you can safely ignore this email.
                                <br>
                                Never share your verification code with anyone.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer band -->
                    <tr>
                        <td style="background-color: #fafafa; padding: 20px 32px; text-align: center; border-top: 1px solid #f0f0f0;">
                            <p style="margin: 0; font-size: 12px; color: #bbb;">
                                &copy; {{ date('Y') }} {{ $appName }}. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- /Main card -->
            </td>
        </tr>
    </table>
    <!-- /Wrapper -->

</body>
</html>
