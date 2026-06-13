<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <style>
        @media only screen and (max-width: 600px) {
            .inner-body { width: 100% !important; }
            .footer { width: 100% !important; }
        }
        @media only screen and (max-width: 500px) {
            .button { width: 100% !important; }
        }
    </style>
</head>
<body style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #ffffff; color: #718096; line-height: 1.4; margin: 0; padding: 0; width: 100% !important;">

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #edf2f7; margin: 0; padding: 0; width: 100%;">
    <tbody>
    <tr>
        <td align="center">
            <table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0; padding: 0; width: 100%;">
                <tbody>
                <tr>
                    <td class="body" width="100%" cellpadding="0" cellspacing="0" style="background-color: #edf2f7; border-bottom: 1px solid #edf2f7; border-top: 1px solid #edf2f7; margin: 0; padding: 0; width: 100%;">
                        <table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #ffffff; border-radius: 2px; box-shadow: 0 2px 0 rgba(0, 0, 150, 0.025); margin: 0 auto; padding: 0; width: 570px;">
                            <tbody>
                            <tr>
                                <td class="content-cell" style="max-width: 100vw; padding: 32px;">
                                    <h1 dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}" style="color: #3d4852; font-size: 18px; font-weight: bold; margin-top: 0; text-align: {{ app()->getLocale() == 'ar' ? 'right' : 'left' }};">
                                        {{ __('workflow.action_required') }}
                                    </h1>
                                    <p style="font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: {{ app()->getLocale() == 'ar' ? 'right' : 'left' }};">
                                        {{ __('workflow.you_have_a_pending_step') }}: <strong>{{ $stepName }}</strong>
                                    </p>
                                    <p style="font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: {{ app()->getLocale() == 'ar' ? 'right' : 'left' }};">
                                        {{ __('workflow.step_order') }}: {{ $stepOrder }}
                                    </p>
                                    <p style="font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: {{ app()->getLocale() == 'ar' ? 'right' : 'left' }};">
                                        {{ __('workflow.please_review_and_act') }}
                                    </p>
                                    <p style="font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: {{ app()->getLocale() == 'ar' ? 'right' : 'left' }};">
                                        {{ __('emails.regards') }},<br>
                                        {{ config('app.name') }}
                                    </p>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 auto; padding: 0; text-align: center; width: 570px;">
                            <tbody>
                            <tr>
                                <td class="content-cell" align="center" style="max-width: 100vw; padding: 32px;">
                                    <p style="line-height: 1.5em; margin-top: 0; color: #b0adc5; font-size: 12px; text-align: center;">
                                        {{ __('emails.rights') }}
                                    </p>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    </tbody>
</table>
</body>
</html>
