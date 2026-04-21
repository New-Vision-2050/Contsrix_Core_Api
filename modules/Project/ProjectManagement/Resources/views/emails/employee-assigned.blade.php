<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>تم تعيينك في مشروع</title>
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
                      إشعارات نظام إدارة المشاريع
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
                <span style="display:inline-block; background:#dcfce7; color:#15803d; font-size:13px; font-weight:700; padding:10px 16px; border-radius:999px;">
                  تعيين جديد
                </span>
              </div>

              <!-- Title -->
              <h1 style="margin:0 0 12px; font-size:28px; line-height:1.6; color:#0f172a; font-weight:700;">
                تم تعيينك في مشروع جديد
              </h1>

              <!-- Greeting -->
              <p style="margin:0 0 10px; font-size:15px; line-height:1.9; color:#334155;">
                عزيزي <strong>{{ $employeeName }}</strong>،
              </p>

              <!-- Message -->
              <p style="margin:0 0 24px; font-size:15px; line-height:2; color:#475569;">
                نود إعلامك بأنه تم تعيينك في مشروع <strong>{{ $projectName }}</strong> من قبل <strong>{{ $assignedByName }}</strong>.
              </p>

              <!-- Project Details Card -->
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:16px; margin-bottom:28px;">
                <tr>
                  <td style="padding:24px;">
                    <table width="100%" cellspacing="0" cellpadding="0">
                      <tr>
                        <td style="padding:8px 0; border-bottom:1px solid #e2e8f0;">
                          <span style="color:#64748b; font-size:13px;">اسم المشروع:</span>
                          <strong style="display:block; color:#0f172a; font-size:15px; margin-top:4px;">{{ $projectName }}</strong>
                        </td>
                      </tr>
                      @if($projectDescription)
                      <tr>
                        <td style="padding:8px 0; border-bottom:1px solid #e2e8f0;">
                          <span style="color:#64748b; font-size:13px;">الوصف:</span>
                          <p style="color:#0f172a; font-size:14px; margin:4px 0 0; line-height:1.6;">{{ $projectDescription }}</p>
                        </td>
                      </tr>
                      @endif
                      <tr>
                        <td style="padding:8px 0; border-bottom:1px solid #e2e8f0;">
                          <span style="color:#64748b; font-size:13px;">الدور:</span>
                          <strong style="display:block; color:#0f172a; font-size:15px; margin-top:4px;">{{ $roleName }}</strong>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding:8px 0;">
                          <span style="color:#64748b; font-size:13px;">تم التعيين بواسطة:</span>
                          <strong style="display:block; color:#0f172a; font-size:15px; margin-top:4px;">{{ $assignedByName }}</strong>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <!-- CTA Button -->
              <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
                <tr>
                  <td align="center">
                    <a href="{{ $actionUrl }}" style="display:inline-block; background:linear-gradient(135deg, #135a96 0%, #0b3c68 100%); color:#ffffff; text-decoration:none; padding:14px 32px; border-radius:12px; font-weight:700; font-size:15px; box-shadow:0 4px 12px rgba(19,90,150,0.25);">
                      عرض المشروع
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 10px; font-size:15px; line-height:2; color:#475569;">
                مع تحيات،<br>
                <strong>فريق Constrix</strong>
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
                © {{ $year }} Constrix. جميع الحقوق محفوظة
              </p>
              <p style="margin:0; font-size:12px; color:#64748b;">
                الدعم الفني:
                <a href="mailto:{{ $supportEmail }}" style="color:#135a96; text-decoration:none;">{{ $supportEmail }}</a>
              </p>
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>

</body>
</html>
