<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale ?? 'tr') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#f1f1f3;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;color:#0d0e12;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border:1px solid #e4e4e8;border-radius:10px;padding:28px 32px;">
                    <tr>
                        <td style="padding-bottom:20px;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding-right:8px;">
                                        {{-- PNG, not inline SVG — Gmail and Outlook strip <svg> from HTML mail. --}}
                                        <img src="{{ asset('brand/upmonia-mark-'.($tone ?? 'ink').'@2x.png') }}" width="16" height="16" alt="">
                                    </td>
                                    <td style="font-size:16px;font-weight:700;letter-spacing:-.02em;">upmonia</td>
                                </tr>
                            </table>
                        </td>
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
