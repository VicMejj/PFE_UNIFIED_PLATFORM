<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:24px;background:#f4f7fb;font-family:Arial,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:16px;padding:32px;">
                    <tr>
                        <td>
                            <p style="margin:0 0 12px;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#2563eb;">
                                Unified Platform
                            </p>
                            <h1 style="margin:0 0 16px;font-size:24px;line-height:1.3;color:#111827;">
                                {{ $title }}
                            </h1>
                            <p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#4b5563;">
                                {{ $intro }}
                            </p>

                            <div style="margin:0 0 24px;padding:18px 20px;background:#eff6ff;border-radius:12px;text-align:center;">
                                <span style="display:block;font-size:30px;letter-spacing:0.3em;font-weight:700;color:#1d4ed8;">
                                    {{ $code }}
                                </span>
                            </div>

                            <p style="margin:0 0 8px;font-size:14px;line-height:1.6;color:#4b5563;">
                                This code expires in {{ $expiresInMinutes }} minute{{ $expiresInMinutes === 1 ? '' : 's' }}.
                            </p>
                            <p style="margin:0;font-size:14px;line-height:1.6;color:#6b7280;">
                                If you did not request this code, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
