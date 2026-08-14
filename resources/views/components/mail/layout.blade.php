@props(['data'])

{{--
    A plain, inline-styled email shell. Email clients ignore the application stylesheet,
    so the few visual decisions an email needs live here rather than in each template.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $data->locale->value) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $data->subject }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f5f7; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif; color:#1f2430;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background-color:#ffffff; border:1px solid #e4e6eb; border-radius:12px;">
                    <tr>
                        <td style="padding:24px 28px 8px 28px;">
                            <p style="margin:0; font-size:13px; font-weight:600; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">
                                {{ $data->applicationName }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:8px 28px 24px 28px; font-size:15px; line-height:24px;">
                            {{ $slot }}
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:16px 28px 24px 28px; border-top:1px solid #e4e6eb;">
                            <p style="margin:0; font-size:12px; line-height:18px; color:#6b7280;">
                                {{ __('mail.client_notifications.footer', ['application' => $data->applicationName]) }}
                            </p>
                            <p style="margin:8px 0 0 0; font-size:12px; line-height:18px; color:#9ca3af;">
                                {{ __('mail.client_notifications.scheduled_for', ['date' => $data->scheduledForLabel]) }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
