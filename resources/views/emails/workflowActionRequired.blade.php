<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ __('emails.workflow-action-required-subject') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#eef2f6; font-family:Tahoma, Arial, sans-serif; direction:{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }};">

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#eef2f6; margin:0; padding:32px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:680px; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 10px 30px rgba(15,76,129,0.10);">
                <tr>
                    <td style="background:linear-gradient(135deg, #0b3c68 0%, #135a96 100%); padding:28px 32px;">
                        <table width="100%" role="presentation" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td align="{{ app()->getLocale() == 'ar' ? 'right' : 'left' }}">
                                    <div style="font-size:28px; font-weight:700; color:#ffffff; letter-spacing:0.4px;">
                                        Constrix
                                    </div>
                                    <div style="font-size:13px; color:#dbeafe; margin-top:6px;">
                                        {{ __('emails.workflow-action-required-subtitle') }}
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="background:#f8fbff; padding:0 32px;">
                        <div style="height:6px; background:linear-gradient(90deg,#2563eb,#10b981,#f59e0b); border-radius:0 0 10px 10px;"></div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;">
                        <div style="margin-bottom:18px;">
                            <span style="display:inline-block; background:#e0edff; color:#1d4ed8; font-size:13px; font-weight:700; padding:10px 16px; border-radius:999px;">
                                {{ __('emails.workflow-action-required-badge') }}
                            </span>
                        </div>

                        <h1 style="margin:0 0 12px; font-size:28px; line-height:1.6; color:#0f172a; font-weight:700;">
                            {{ __('emails.workflow-action-required-subject') }}
                        </h1>

                        <p style="margin:0 0 10px; font-size:15px; line-height:1.9; color:#334155;">
                            {{ __('emails.greeting', ['name' => $data['name']]) }}
                        </p>

                        <p style="margin:0 0 24px; font-size:15px; line-height:2; color:#475569;">
                            {{ __('emails.workflow-action-required-message') }}
                        </p>

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:16px; margin-bottom:28px;">
                            <tr>
                                <td style="padding:20px;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                        <tr>
                                            <td style="padding:10px 0; font-size:14px; color:#64748b; width:34%; border-bottom:1px solid #e2e8f0;">
                                                <strong>{{ __('emails.workflow-step-name') }}</strong>
                                            </td>
                                            <td style="padding:10px 0; font-size:14px; color:#0f172a; border-bottom:1px solid #e2e8f0;">
                                                {{ $data['step_name'] }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:10px 0; font-size:14px; color:#64748b;">
                                                <strong>{{ __('emails.workflow-step-order') }}</strong>
                                            </td>
                                            <td style="padding:10px 0; font-size:14px; color:#0f172a;">
                                                {{ $data['step_order'] ?? '-' }}
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0 0 10px; font-size:15px; line-height:2; color:#475569;">
                            {{ __('emails.regards') }},<br>
                            <strong>{{ __('emails.new-vision') }}</strong>
                        </p>

                        <div style="background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; font-size:13px; line-height:1.9; padding:14px 16px; border-radius:12px; margin-top:24px;">
                            {{ __('emails.workflow-automated-message') }}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:22px 32px; background:#f8fafc; border-top:1px solid #e2e8f0; text-align:center;">
                        <p style="margin:0 0 8px; font-size:12px; color:#64748b;">
                            © {{ date('Y') }} Constrix. {{ __('emails.rights') }}
                        </p>
                        <p style="margin:0; font-size:12px; color:#64748b;">
                            {{ __('emails.support') }}:
                            <a href="mailto:info@nv2030.com" style="color:#135a96; text-decoration:none;">info@nv2030.com</a>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
