<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <style>
        @media only screen and (max-width: 600px) {
            .inner-body, .footer {
                width: 100% !important;
            }
        }
        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #fff; color: #718096; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.4;">

@php
    $dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
    $align = app()->getLocale() === 'ar' ? 'right' : 'left';
@endphp

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" style="background-color: #edf2f7; width: 100%;">
    <tr>
        <td align="center">
            <table class="content" width="100%" cellpadding="0" cellspacing="0">
                <!-- Email Body -->
                <tr>
                    <td class="body" width="100%" style="background-color: #edf2f7; border-top: 1px solid #edf2f7; border-bottom: 1px solid #edf2f7;">
                        <table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border: 1px solid #e8e5ef; border-radius: 2px; box-shadow: 0 2px 0 rgba(0, 0, 150, 0.025), 2px 4px 0 rgba(0, 0, 150, 0.015); width: 570px; margin: 0 auto;">
                            <tr>
                                <td class="content-cell" style="padding: 32px;">
                                    <h1 dir="{{ $dir }}" style="color: #3d4852; font-size: 18px; font-weight: bold; text-align: {{ $align }}; margin-top: 0;">
                                        {{ __("emails.welcome") . " : " . $data['name'] }}
                                    </h1>

                                    <p style="font-size: 16px; line-height: 1.5em; text-align: {{ $align }};">
                                        {{ __("emails.you-tried-to-change-password-with") . " : " . $data['email'] }}
                                    </p>

                                    <p style="font-size: 16px; line-height: 1.5em; text-align: {{ $align }};">
                                        <b>{{ __("emails.your-verification-code-is") . " : " . $data['otp'] }}</b>
                                    </p>

                                    <p style="font-size: 16px; line-height: 1.5em; text-align: {{ $align }};">
                                        <b>{{ __("emails.will-expire", ["time" => $data["minutes"]]) }}</b>
                                    </p>

                                    <p style="font-size: 16px; line-height: 1.5em; text-align: {{ $align }};">
                                        {{ __('emails.regards') }},<br>
                                        {{ __("emails.new-vision") }}
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td>
                        <table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" style="text-align: center; width: 570px; margin: 0 auto;">
                            <tr>
                                <td class="content-cell" align="center" style="padding: 32px;">
                                    <p style="font-size: 12px; color: #b0adc5; line-height: 1.5em;">
                                        {{ __("emails.rights") }}
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
