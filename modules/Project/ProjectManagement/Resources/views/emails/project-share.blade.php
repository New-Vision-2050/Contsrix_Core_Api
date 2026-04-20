<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Constrix Notification</title>
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
                <span style="display:inline-block; background:#e0edff; color:#1d4ed8; font-size:13px; font-weight:700; padding:10px 16px; border-radius:999px;">
                  مطلوب موافقة
                </span>
              </div>

              <!-- Title -->
              <h1 style="margin:0 0 12px; font-size:28px; line-height:1.6; color:#0f172a; font-weight:700;">
                طلب موافقة على مشاركة مشروع
              </h1>

              <!-- Greeting -->
              <p style="margin:0 0 10px; font-size:15px; line-height:1.9; color:#334155;">
                مرحبًا <strong>{{ $userName }}</strong>،
              </p>

              <!-- Message -->
              <p style="margin:0 0 24px; font-size:15px; line-height:2; color:#475569;">
                يوجد طلب موافقة على مشاركة المشروع رقم <strong>{{ $referenceNo }}</strong> 
                بعنوان <strong>{{ $projectName }}</strong>. برجاء مراجعة التفاصيل واتخاذ الإجراء المناسب.
              </p>

              <!-- Details Card -->
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:16px; margin-bottom:28px;">
                <tr>
                  <td style="padding:20px;">

                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                      <tr>
                        <td style="padding:10px 0; font-size:14px; color:#64748b; width:34%; border-bottom:1px solid #e2e8f0;">
                          <strong>المشروع</strong>
                        </td>
                        <td style="padding:10px 0; font-size:14px; color:#0f172a; border-bottom:1px solid #e2e8f0;">
                          {{ $projectName }}
                        </td>
                      </tr>

                      <tr>
                        <td style="padding:10px 0; font-size:14px; color:#64748b; border-bottom:1px solid #e2e8f0;">
                          <strong>رقم المرجع</strong>
                        </td>
                        <td style="padding:10px 0; font-size:14px; color:#0f172a; border-bottom:1px solid #e2e8f0;">
                          {{ $referenceNo }}
                        </td>
                      </tr>

                      <tr>
                        <td style="padding:10px 0; font-size:14px; color:#64748b; border-bottom:1px solid #e2e8f0;">
                          <strong>نوع العنصر</strong>
                        </td>
                        <td style="padding:10px 0; font-size:14px; color:#0f172a; border-bottom:1px solid #e2e8f0;">
                          {{ $entityType }}
                        </td>
                      </tr>

                      <tr>
                        <td style="padding:10px 0; font-size:14px; color:#64748b; border-bottom:1px solid #e2e8f0;">
                          <strong>اسم العنصر</strong>
                        </td>
                        <td style="padding:10px 0; font-size:14px; color:#0f172a; border-bottom:1px solid #e2e8f0;">
                          {{ $entityName }}
                        </td>
                      </tr>

                      <tr>
                        <td style="padding:10px 0; font-size:14px; color:#64748b; border-bottom:1px solid #e2e8f0;">
                          <strong>الحالة</strong>
                        </td>
                        <td style="padding:10px 0; font-size:14px; color:#d97706; border-bottom:1px solid #e2e8f0; font-weight:700;">
                          {{ $status }}
                        </td>
                      </tr>

                      <tr>
                        <td style="padding:10px 0; font-size:14px; color:#64748b; border-bottom:1px solid #e2e8f0;">
                          <strong>الأولوية</strong>
                        </td>
                        <td style="padding:10px 0; font-size:14px; color:#dc2626; border-bottom:1px solid #e2e8f0; font-weight:700;">
                          {{ $priority }}
                        </td>
                      </tr>

                      <tr>
                        <td style="padding:10px 0; font-size:14px; color:#64748b; border-bottom:1px solid #e2e8f0;">
                          <strong>مقدم الطلب</strong>
                        </td>
                        <td style="padding:10px 0; font-size:14px; color:#0f172a; border-bottom:1px solid #e2e8f0;">
                          {{ $senderName }}
                        </td>
                      </tr>

                      <tr>
                        <td style="padding:10px 0; font-size:14px; color:#64748b;">
                          <strong>التاريخ</strong>
                        </td>
                        <td style="padding:10px 0; font-size:14px; color:#0f172a;">
                          {{ $createdAt }}
                        </td>
                      </tr>
                    </table>

                  </td>
                </tr>
              </table>

              <!-- CTA -->
              <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto 22px auto;">
                <tr>
                  <td align="center" bgcolor="#135a96" style="border-radius:12px;">
                    <a href="{{ $actionUrl }}" target="_blank" style="display:inline-block; padding:15px 28px; font-size:15px; font-weight:700; color:#ffffff; text-decoration:none; border-radius:12px;">
                      مراجعة الطلب
                    </a>
                  </td>
                </tr>
              </table>

              <!-- Secondary Text -->
              <p style="margin:0 0 10px; font-size:13px; line-height:1.8; color:#64748b;">
                إذا لم يعمل الزر، يمكنك نسخ الرابط التالي وفتحه من المتصفح:
              </p>

              <p style="margin:0 0 24px; font-size:13px; line-height:1.9; color:#2563eb; direction:ltr; text-align:left; word-break:break-all;">
                {{ $actionUrl }}
              </p>

              <!-- Note -->
              <div style="background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; font-size:13px; line-height:1.9; padding:14px 16px; border-radius:12px; margin-bottom:8px;">
                هذه رسالة آلية من نظام <strong>Constrix</strong>، برجاء عدم الرد على هذا البريد.
              </div>

            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:22px 32px; background:#f8fafc; border-top:1px solid #e2e8f0; text-align:center;">
              <p style="margin:0 0 8px; font-size:12px; color:#64748b;">
                © {{ $year }} Constrix. جميع الحقوق محفوظة.
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
