<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verified</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            -webkit-font-smoothing: antialiased;
        }
        .card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.08);
            max-width: 460px;
            width: 100%;
            overflow: hidden;
            text-align: center;
        }
        .band {
            height: 6px;
            background: linear-gradient(135deg, #CE1126 0%, #FCD116 50%, #006B3F 100%);
        }
        .body { padding: 40px 32px; }
        .icon-wrap {
            width: 72px; height: 72px;
            border-radius: 50%;
            background: #ecfdf5;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
        }
        .icon-wrap svg { width: 36px; height: 36px; color: #16a34a; }
        h1 { font-size: 22px; color: #1a1a2e; margin-bottom: 12px; font-weight: 700; }
        .msg { font-size: 15px; color: #555; line-height: 1.6; margin-bottom: 24px; }
        .sms-note {
            background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px;
            padding: 14px 18px; font-size: 14px; color: #92400e; line-height: 1.5;
            margin-bottom: 24px;
        }
        .close-hint { font-size: 13px; color: #999; }
        .footer {
            background: #fafafa; border-top: 1px solid #f0f0f0;
            padding: 16px 32px; font-size: 12px; color: #bbb;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="band"></div>
        <div class="body">
            <div class="icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h1>Email Verified!</h1>
            <p class="msg">
                Your email address <strong>{{ $email }}</strong> has been successfully verified.
            </p>

            @if($smsSent)
                <div class="sms-note">
                    An SMS with your verification code has also been sent to your phone number for your records.
                </div>
            @endif

            <p class="close-hint">
                You can close this tab and return to the registration form to continue.
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name', 'One Million Coders') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
