<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ __($subjectKey, ['ticket' => $ticket->ticket_code, ...$subjectReplacements]) }}</title>
</head>
@php
    $clientName = trim($ticket->first_name.' '.$ticket->last_name) ?: $ticket->email;
    $serviceName = $ticket->service?->localizedName() ?? __('site.pending_assignment');
@endphp
<body style="margin:0;padding:0;background:#f5f3ee;color:#1c1917;font-family:Georgia,'Times New Roman',serif;-webkit-text-size-adjust:100%;text-size-adjust:100%;">
    <span style="display:none!important;visibility:hidden;opacity:0;color:transparent;height:0;width:0;overflow:hidden;mso-hide:all;">
        {{ __($preheaderKey) }}
    </span>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;background:#f5f3ee;padding:32px 14px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;max-width:680px;border-collapse:separate;border-spacing:0;">
                    <tr>
                        <td style="background:#0f0f0d;border-radius:28px 28px 0 0;padding:32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="vertical-align:middle;width:66px;">
                                        @if (! empty($brand['logo_url']))
                                            <img src="{{ $brand['logo_url'] }}" alt="{{ $brand['company_name'] }}" width="58" height="58" style="display:block;width:58px;height:58px;border-radius:999px;object-fit:cover;border:1px solid #d8e4ca;">
                                        @else
                                            <div style="width:58px;height:58px;border-radius:999px;background:#5f6f52;color:#ffffff;text-align:center;line-height:58px;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:700;letter-spacing:0.08em;">{{ $brand['logo_text'] ?? 'IG' }}</div>
                                        @endif
                                    </td>
                                    <td style="vertical-align:middle;padding-left:16px;">
                                        <div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;letter-spacing:4px;text-transform:uppercase;color:#c7d2b8;font-weight:700;">{{ $brand['company_name'] ?? 'IGNA Studio' }}</div>
                                        <div style="margin-top:6px;color:#f8f7f2;font-size:24px;line-height:1.25;font-weight:700;">{{ __($titleKey) }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#ffffff;border-left:1px solid #e7e1d6;border-right:1px solid #e7e1d6;padding:34px 32px 8px 32px;">
                            <div style="display:inline-block;border:1px solid #d8e4ca;border-radius:999px;padding:8px 14px;color:#5f6f52;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;">
                                {{ $ticket->ticket_code }}
                            </div>

                            <h1 style="margin:24px 0 0;color:#11100f;font-size:32px;line-height:1.18;font-weight:700;letter-spacing:-0.02em;">{{ __($headlineKey) }}</h1>
                            <p style="margin:18px 0 0;color:#57534e;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.75;">{{ __($messageKey, $messageReplacements) }}</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#ffffff;border-left:1px solid #e7e1d6;border-right:1px solid #e7e1d6;padding:22px 32px 0 32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8f7f2;border:1px solid #ece7dc;border-radius:22px;">
                                <tr>
                                    <td style="padding:22px 22px 4px 22px;">
                                        <div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#7a6f61;">{{ __('site.email_project_summary') }}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 22px 22px 22px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td style="padding:12px 0;border-bottom:1px solid #e7e1d6;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#78716c;">{{ __('site.form_project_name') }}</td>
                                                <td align="right" style="padding:12px 0;border-bottom:1px solid #e7e1d6;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#1c1917;font-weight:700;">{{ $ticket->localizedProjectName() }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:12px 0;border-bottom:1px solid #e7e1d6;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#78716c;">{{ __('site.email_client_label') }}</td>
                                                <td align="right" style="padding:12px 0;border-bottom:1px solid #e7e1d6;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#1c1917;font-weight:700;">{{ $clientName }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:12px 0;border-bottom:1px solid #e7e1d6;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#78716c;">{{ __('site.form_email') }}</td>
                                                <td align="right" style="padding:12px 0;border-bottom:1px solid #e7e1d6;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#1c1917;font-weight:700;">{{ $ticket->email }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:12px 0;border-bottom:1px solid #e7e1d6;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#78716c;">{{ __('site.form_phone') }}</td>
                                                <td align="right" style="padding:12px 0;border-bottom:1px solid #e7e1d6;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#1c1917;font-weight:700;">{{ $ticket->phone ?: '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:12px 0;border-bottom:1px solid #e7e1d6;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#78716c;">{{ __('site.email_service_label') }}</td>
                                                <td align="right" style="padding:12px 0;border-bottom:1px solid #e7e1d6;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#1c1917;font-weight:700;">{{ $serviceName }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:12px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#78716c;">{{ __('site.email_location_label') }}</td>
                                                <td align="right" style="padding:12px 0;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#1c1917;font-weight:700;">{{ $ticket->project_location ?: '-' }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#ffffff;border-left:1px solid #e7e1d6;border-right:1px solid #e7e1d6;padding:22px 32px 4px 32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #d8e4ca;border-radius:22px;">
                                <tr>
                                    <td style="padding:20px 22px;">
                                        <div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#5f6f52;">{{ __('site.form_project_description') }}</div>
                                        <p style="margin:10px 0 0;color:#57534e;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.7;">{{ $ticket->localizedProjectDescription() }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#ffffff;border-left:1px solid #e7e1d6;border-right:1px solid #e7e1d6;padding:24px 32px 34px 32px;">
                            <table role="presentation" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="border-radius:999px;background:#5f6f52;">
                                        <a href="{{ $adminTicketUrl }}" style="display:inline-block;padding:14px 24px;border-radius:999px;color:#ffffff;text-decoration:none;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:700;">{{ __('site.email_admin_open_ticket') }}</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#f0eee7;border:1px solid #e7e1d6;border-top:0;border-radius:0 0 28px 28px;padding:22px 32px;text-align:center;">
                            <p style="margin:0;color:#57534e;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.7;">{{ __('site.email_admin_footer_note') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
