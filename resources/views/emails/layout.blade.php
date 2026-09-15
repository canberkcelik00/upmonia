<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale ?? 'tr') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:#171717;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;padding:32px;">
                    <tr>
                        <td style="font-size:18px;font-weight:600;padding-bottom:24px;">Uptik</td>
                    </tr>
                    <tr>
                        <td style="font-size:14px;line-height:1.6;">
                            {{ $slot }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
