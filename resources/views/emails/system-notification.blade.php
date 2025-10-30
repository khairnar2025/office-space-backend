<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $subjectText }}</title>
</head>

<body style="margin:0; padding:0; background:#f4f7fa; font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">

    <div
        style="max-width:650px; margin:40px auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.1);">

        <!-- Header -->
        <div
            style="background:linear-gradient(to bottom, #325B39, #244129); color:#2c3e50; padding:30px 20px; text-align:center;">
            <h1 style="margin:0; font-size:24px; font-weight:700;">{{ $title }}</h1>
            <p style="margin:5px 0 0; font-size:14px; opacity:0.85;">{{ \Carbon\Carbon::now()->format('F d, Y') }}</p>
        </div>

        <!-- Body -->
        <div style="padding:30px; text-align:center;">
            <h2 style="color:#2c3e50; margin-top:0;">Hello, {{ $user->full_name }} 👋</h2>
            <p style="font-size:15px; line-height:1.6; color:#444; margin:15px 0;">
                {!! $emailContent !!}
            </p>

            @if ($ctaText && $ctaUrl)
                <p style="margin-top:25px;">
                    <a href="{{ $ctaUrl }}"
                        style="display:inline-block; background:#244129; color:#2c3e50; padding:12px 25px; border-radius:8px; text-decoration:none; font-weight:600;">
                        {{ $ctaText }}
                    </a>
                </p>
            @endif
        </div>

        <!-- Footer -->
        <div style="background:#f9f9f9; padding:15px; text-align:center; font-size:13px; color:#555;">
            Best Regards,<br>
            <strong>{{ config('app.name') }} Team</strong><br><br>
            <span style="font-size:12px; color:#999;">© {{ date('Y') }} {{ config('app.name') }}. All rights
                reserved.</span>
        </div>
    </div>

</body>

</html>
