<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Juankker ERP' }}</title>
</head>
<body style="margin:0;padding:0;background:#eef2f5;font-family:'Segoe UI',Helvetica,Arial,sans-serif;color:#1e3a8a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f5;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 4px 24px rgba(15,41,66,.08);">
                    <tr>
                        <td style="background:#1e3a8a;padding:26px 32px;">
                            <span style="display:inline-block;width:28px;height:28px;background:#2563eb;border-radius:8px;vertical-align:middle;"></span>
                            <span style="color:#ffffff;font-size:20px;font-weight:700;vertical-align:middle;margin-left:10px;">Juankker</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;font-size:15px;line-height:1.6;">
                            {!! $slot ?? '' !!}
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px;background:#f8fafc;color:#64748b;font-size:12px;text-align:center;border-top:1px solid #e2e8f0;">
                            Juankker · Soluciones ERP y software empresarial · Aguascalientes, México
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
