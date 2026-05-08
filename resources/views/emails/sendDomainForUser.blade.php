<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ __('emails.welcome') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#eef2f6; font-family:Tahoma, Arial, sans-serif; direction:rtl;">

  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#eef2f6; margin:0; padding:32px 0;">
    <tr>
      <td align="center">

        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:680px; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 10px 30px rgba(15,76,129,0.10);">

          <!-- Header -->
          <tr>
            <td style="background:linear-gradient(135deg, #0b3c68 0%, #135a96 100%); padding:28px 32px;">
              <table width="100%" role="presentation" cellspacing="0" cellpadding="0" border="0">
                <tr>
                  <td align="right">
                    <div style="font-size:28px; font-weight:700; color:#ffffff; letter-spacing:0.4px;">
                      Constrix
                    </div>
                    <div style="font-size:13px; color:#dbeafe; margin-top:6px;">
                      إضافتك لشركة جديدة
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Top Accent -->
          <tr>
            <td style="background:#f8fbff; padding:0 32px;">
              <div style="height:6px; background:linear-gradient(90deg,#2563eb,#10b981,#f59e0b); border-radius:0 0 10px 10px;"></div>
            </td>
          </tr>

          <!-- Content -->
          <tr>
            <td style="padding:32px;">

              <!-- Badge -->
              <div style="margin-bottom:18px;">
                <span style="display:inline-block; background:#e0edff; color:#1d4ed8; font-size:13px; font-weight:700; padding:10px 16px; border-radius:999px;">
                  معلومات الوصول
                </span>
              </div>

              <!-- Title -->
              <h1 style="margin:0 0 12px; font-size:28px; line-height:1.6; color:#0f172a; font-weight:700;">
                {{ __("emails.welcome") }}
              </h1>

              <!-- Greeting -->
              <p style="margin:0 0 10px; font-size:15px; line-height:1.9; color:#334155;">
                مرحبًا <strong>{{ $data['name'] }}</strong>،
              </p>

              <!-- Message -->
              <p style="margin:0 0 24px; font-size:15px; line-height:2; color:#475569;">
                {{ __("emails.you-are-added-to-company",["company"=>$data["company_name"]]) }}
              </p>

              <!-- Details Card -->
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:16px; margin-bottom:28px;">
                <tr>
                  <td style="padding:20px;">

                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                      <tr>
                        <td style="padding:10px 0; font-size:14px; color:#64748b; width:34%; border-bottom:1px solid #e2e8f0;">
                          <strong>{{ __("emails.your-domain-is") }}</strong>
                        </td>
                        <td style="padding:10px 0; font-size:14px; color:#0f172a; border-bottom:1px solid #e2e8f0;">
                          <a href="{{$data['domain_name']}}" style="color:#135a96; text-decoration:none;">{{$data['domain_name']}}</a>
                        </td>
                      </tr>

                      <tr>
                        <td style="padding:10px 0; font-size:14px; color:#64748b;">
                          <strong>{{ __("emails.serial_no") }}</strong>
                        </td>
                        <td style="padding:10px 0; font-size:14px; color:#0f172a;">
                          {{$data['serial_no']}}
                        </td>
                      </tr>
                    </table>

                  </td>
                </tr>
              </table>

              <p style="margin:0 0 10px; font-size:15px; line-height:2; color:#475569;">
                {{__('emails.regards')}},<br>
                <strong>{{__("emails.new-vision")}}</strong>
              </p>

              <!-- Note -->
              <div style="background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; font-size:13px; line-height:1.9; padding:14px 16px; border-radius:12px; margin-top:24px;">
                هذه رسالة آلية من نظام <strong>Constrix</strong>، برجاء عدم الرد على هذا البريد.
              </div>

            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:22px 32px; background:#f8fafc; border-top:1px solid #e2e8f0; text-align:center;">
              <p style="margin:0 0 8px; font-size:12px; color:#64748b;">
                © {{ date('Y') }} Constrix. {{__("emails.rights")}}
              </p>
              <p style="margin:0; font-size:12px; color:#64748b;">
                الدعم الفني:
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
