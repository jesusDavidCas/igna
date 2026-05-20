<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $headline }}</title>
</head>
<body style="margin:0;background:#f5f3ee;color:#1c1917;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5f3ee;padding:28px 14px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #e7e5e4;border-radius:24px;overflow:hidden;">
                    <tr>
                        <td style="background:#1c1917;padding:28px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        @if (! empty($brand['logo_url']))
                                            <img src="{{ $brand['logo_url'] }}" alt="{{ $brand['company_name'] }}" width="52" height="52" style="display:block;border-radius:999px;object-fit:cover;">
                                        @else
                                            <div style="width:52px;height:52px;border-radius:999px;background:#5f6f52;color:#fff;text-align:center;line-height:52px;font-weight:700;">{{ $brand['logo_text'] }}</div>
                                        @endif
                                    </td>
                                    <td style="padding-left:14px;color:#f5f3ee;">
                                        <div style="font-size:13px;letter-spacing:2px;text-transform:uppercase;color:#c7d2b8;">{{ $brand['company_name'] }}</div>
                                        <div style="font-size:20px;font-weight:700;margin-top:4px;">{{ __('site.email_project_update') }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#78716c;">{{ $ticket->ticket_code }}</p>
                            <h1 style="margin:0;color:#1c1917;font-size:26px;line-height:1.25;">{{ $headline }}</h1>
                            <p style="margin:16px 0 0;color:#57534e;font-size:15px;line-height:1.7;">{{ $message ?: __('site.email_default_message') }}</p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:24px;background:#f7f6f2;border-radius:18px;padding:18px;">
                                <tr>
                                    <td style="font-size:13px;color:#78716c;">{{ __('site.form_project_name') }}</td>
                                    <td style="font-size:14px;font-weight:700;color:#1c1917;text-align:right;">{{ $ticket->localizedProjectName() }}</td>
                                </tr>
                                <tr>
                                    <td style="padding-top:12px;font-size:13px;color:#78716c;">{{ __('site.current_stage') }}</td>
                                    <td style="padding-top:12px;font-size:14px;font-weight:700;color:#1c1917;text-align:right;">{{ $ticket->currentStage?->localizedName() ?? __('site.pending_assignment') }}</td>
                                </tr>
                            </table>

                            <p style="margin:28px 0 0;">
                                <a href="{{ $trackingUrl }}" style="display:inline-block;background:#5f6f52;color:#ffffff;text-decoration:none;border-radius:999px;padding:13px 22px;font-size:14px;font-weight:700;">{{ __('site.email_view_tracking') }}</a>
                            </p>
                            <p style="margin:24px 0 0;color:#78716c;font-size:12px;line-height:1.6;">{{ __('site.email_footer_note') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
