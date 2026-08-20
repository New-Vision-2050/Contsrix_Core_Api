<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>إشعار مخالفة</title>
</head>
<body style="margin:0; padding:0; background-color:#ffffff; font-family:Tahoma, Arial, sans-serif; direction:rtl; text-align:right;">

  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#ffffff; margin:0; padding:24px 12px;">
    <tr>
      <td align="center">

        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:680px; background:#ffffff; border:1px solid #d0d0d0;">

          <!-- Header / Branding -->
          <tr>
            <td align="center" style="padding:22px 20px 8px 20px;">
              <div style="font-size:20px; font-weight:700; color:#111111; line-height:1.5;">
                أبعاد الرؤية للاستشارات الهندسية
              </div>
              <div style="font-size:12px; color:#333333; line-height:1.5; margin-top:2px; direction:ltr; text-align:center;">
                Vision Dimensions for Engineering Consultations
              </div>
            </td>
          </tr>

          <!-- Title -->
          <tr>
            <td align="center" style="padding:6px 20px 16px 20px;">
              <div style="font-size:22px; font-weight:700; color:#2e7d32; line-height:1.4;">
                إشعار مخالفة
              </div>
            </td>
          </tr>

          <!-- Salutation + Intro -->
          <tr>
            <td style="padding:0 22px 16px 22px; font-size:14px; line-height:2; color:#111111;">
              <div style="margin-bottom:8px;">
                السادة شركة / {{ $contractor_name }} المحترمين
              </div>
              <div style="margin-bottom:10px;">
                تحية طيبة وبعد،،،
              </div>
              <div>
                نفيدكم بأنه قد تم تسجيل مخالفة سلامة على الأعمال المرتبطة بأمر العمل رقم {{ $work_order }}، وعليه نأمل اتخاذ اللازم بشكل عاجل.
              </div>
            </td>
          </tr>

          <!-- Main Table -->
          <tr>
            <td style="padding:0 22px 18px 22px;">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" dir="rtl" style="border-collapse:collapse; border:1px solid #bfbfbf; width:100%; font-size:13px; color:#111111;">

                <!-- Row: نوع الإشعار / تاريخ الإصدار -->
                <tr>
                  <td style="border:1px solid #bfbfbf; padding:8px 10px; width:18%; font-weight:700; background:#ffffff; text-align:center; white-space:nowrap;">نوع الإشعار</td>
                  <td style="border:1px solid #bfbfbf; padding:8px 10px; width:32%; background:#f8d7da; text-align:center;">{{ $notification_type }}</td>
                  <td style="border:1px solid #bfbfbf; padding:8px 10px; width:18%; font-weight:700; background:#ffffff; text-align:center; white-space:nowrap;">تاريخ الإصدار</td>
                  <td style="border:1px solid #bfbfbf; padding:8px 10px; width:32%; background:#f8d7da; text-align:center;">{{ $issue_date }}</td>
                </tr>

                <!-- Row: أمر العمل / وقت الزيارة -->
                <tr>
                  <td style="border:1px solid #bfbfbf; padding:8px 10px; font-weight:700; background:#ffffff; text-align:center;">أمر العمل</td>
                  <td style="border:1px solid #bfbfbf; padding:8px 10px; background:#f8d7da; text-align:center;">{{ $work_order }}</td>
                  <td style="border:1px solid #bfbfbf; padding:8px 10px; font-weight:700; background:#ffffff; text-align:center;">وقت الزيارة</td>
                  <td style="border:1px solid #bfbfbf; padding:8px 10px; background:#f8d7da; text-align:center;">{{ $visit_time }}</td>
                </tr>

                <!-- Row: المقاول / مكان المخالفة -->
                <tr>
                  <td style="border:1px solid #bfbfbf; padding:8px 10px; font-weight:700; background:#ffffff; text-align:center;">المقاول</td>
                  <td style="border:1px solid #bfbfbf; padding:8px 10px; background:#f8d7da; text-align:center;">{{ $contractor_name }}</td>
                  <td style="border:1px solid #bfbfbf; padding:8px 10px; font-weight:700; background:#ffffff; text-align:center;">مكان المخالفة</td>
                  <td style="border:1px solid #bfbfbf; padding:8px 10px; background:#f8d7da; text-align:center;">{{ $location }}</td>
                </tr>

                <!-- Row: مدير المشروع / مسؤول السلامة -->
                <tr>
                  <td style="border:1px solid #bfbfbf; padding:8px 10px; font-weight:700; background:#ffffff; text-align:center;">مدير المشروع</td>
                  <td style="border:1px solid #bfbfbf; padding:8px 10px; background:#f8d7da; text-align:center;">{{ $project_manager }}</td>
                  <td style="border:1px solid #bfbfbf; padding:8px 10px; font-weight:700; background:#ffffff; text-align:center;">مسؤول السلامة</td>
                  <td style="border:1px solid #bfbfbf; padding:8px 10px; background:#f8d7da; text-align:center;">{{ $safety_officer }}</td>
                </tr>

                <!-- Row: مشرف الموقع (empty for now) -->
                <tr>
                  <td style="border:1px solid #bfbfbf; padding:8px 10px; font-weight:700; background:#ffffff; text-align:center;">مشرف الموقع</td>
                  <td colspan="3" style="border:1px solid #bfbfbf; padding:8px 10px; background:#f8d7da; text-align:center;">{{ $site_supervisor }}</td>
                </tr>

                <!-- Row: كود المخالفة الأولى / إجمالي الغرامة -->
                <tr>
                  <td style="border:1px solid #bfbfbf; padding:8px 10px; font-weight:700; background:#ffffff; text-align:center;">كود المخالفة</td>
                  <td style="border:1px solid #bfbfbf; padding:8px 10px; background:#f8d7da; text-align:center;">{{ $first_violation_code }}</td>
                  <td style="border:1px solid #bfbfbf; padding:8px 10px; font-weight:700; background:#ffffff; text-align:center;">إجمالي الغرامة</td>
                  <td style="border:1px solid #bfbfbf; padding:8px 10px; background:#f8d7da; text-align:center;">{{ $total_fine }}</td>
                </tr>

                <!-- Violations (dynamic) -->
                @foreach ($violations as $violation)
                  <tr>
                    <td style="border:1px solid #bfbfbf; padding:8px 10px; font-weight:700; background:#ffffff; text-align:center; white-space:nowrap;">{{ $violation['label'] }}</td>
                    <td colspan="3" style="border:1px solid #bfbfbf; padding:8px 10px; background:#f8d7da; text-align:center;">{{ $violation['value'] }}</td>
                  </tr>
                @endforeach

                <!-- Report link (empty for now) -->
                <tr>
                  <td style="border:1px solid #bfbfbf; padding:8px 10px; font-weight:700; background:#ffffff; text-align:center;">رابط المحضر</td>
                  <td colspan="3" style="border:1px solid #bfbfbf; padding:8px 10px; background:#f8d7da; text-align:center;">
                    @if (! empty($report_url))
                      <a href="{{ $report_url }}" target="_blank" style="color:#111111; font-weight:700; text-decoration:underline;">فتح الرابط</a>
                    @else
                      <span style="color:#111111; font-weight:700;">فتح الرابط</span>
                    @endif
                  </td>
                </tr>

              </table>
            </td>
          </tr>

          <!-- Instructions -->
          <tr>
            <td style="padding:4px 22px 12px 22px; font-size:13px; line-height:2; color:#111111;">
              يرجى التكرم بتوقيع محضر المخالفة المرفق بصيغة PDF ضمن هذه الرسالة، وإعادة إرساله بعد التوقيع من خلال الرد على نفس البريد.
            </td>
          </tr>

          <!-- Disclaimer -->
          <tr>
            <td style="padding:0 22px 18px 22px; font-size:13px; line-height:2; color:#c00000; font-weight:700;">
              في حال وجود أي اعتراض أو مبررات على المخالفة، يلزم الرد على هذا البريد خلال مدة لا تتجاوز 24 ساعة من وقت الإرسال، وفي حال عدم الرد خلال المدة المحددة، يُعد ذلك إقراراً من قبلكم بصحة المخالفة والموافقة على اعتمادها، مع استكمال الإجراءات النظامية اللازمة بشأنها.
            </td>
          </tr>

          <!-- Footer branding -->
          <tr>
            <td align="center" style="padding:8px 20px 4px 20px;">
              <div style="font-size:16px; font-weight:700; color:#111111; line-height:1.5;">
                أبعاد الرؤية للاستشارات الهندسية
              </div>
              <div style="font-size:11px; color:#333333; line-height:1.5; margin-top:2px; direction:ltr; text-align:center;">
                Vision Dimensions for Engineering Consultations
              </div>
            </td>
          </tr>

          <!-- System note -->
          <tr>
            <td align="center" style="padding:10px 20px 22px 20px; font-size:11px; color:#888888; line-height:1.6;">
              هذه رسالة آلية صادرة من نظام المتابعة والتدقيق
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>

</body>
</html>
