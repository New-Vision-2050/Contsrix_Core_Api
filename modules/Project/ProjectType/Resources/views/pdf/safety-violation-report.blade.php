<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        /*
         * STATIC FORM TEMPLATE — fixed geometry only.
         * Dynamic values are injected into reserved cells; nothing may reflow.
         */
        body {
            font-family: arial, tahoma, dejavusans, sans-serif;
            font-size: 9.5pt;
            color: #000;
            direction: rtl;
            line-height: 1.25;
        }

        .bg { background-color: #bdd3e9; }
        .page {
            border: 1px solid #000;
            padding: 1px;
            width: 100%;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
        }

        .t td {
            border: 0.7pt solid #000;
            padding: 2px 3px;
            vertical-align: middle;
            text-align: center;
            font-size: 9.5pt;
            font-weight: bold;
            overflow: hidden;
        }

        .logo-row td { border: none !important; padding: 0 2px 3px 2px; height: 18mm; }
        .logo { width: 62px; height: 62px; }

        .title { font-size: 10pt; font-weight: bold; text-align: center; padding: 4px !important; height: 8mm; }
        .lbl { background-color: #bdd3e9; font-size: 9pt; font-weight: bold; text-align: center; height: 7.2mm; }
        .val {
            font-size: 9pt;
            font-weight: bold;
            text-align: center;
            height: 7.2mm;
            overflow: hidden;
            word-wrap: break-word;
        }
        .sec { background-color: #bdd3e9; font-size: 9.5pt; font-weight: bold; padding: 3px 5px !important; height: 7.5mm; }

        .code {
            font-size: 9pt;
            font-weight: bold;
            text-align: center;
            height: 7mm;
            overflow: hidden;
        }

        .ref-ar { font-size: 7.8pt; font-weight: bold; text-align: center; line-height: 1.25; }
        .ref-en { font-size: 6.2pt; font-weight: normal; text-align: center; direction: ltr; line-height: 1.15; }
        .rep-ar { font-size: 9.5pt; font-weight: bold; text-align: center; }
        .rep-en { font-size: 6.5pt; font-weight: normal; text-align: center; direction: ltr; }

        .hdr-cell { height: 11mm; overflow: hidden; }
        .desc {
            font-size: 8.5pt;
            font-weight: bold;
            text-align: center;
            height: 12mm;
            overflow: hidden;
            word-wrap: break-word;
        }
        .repv {
            font-size: 9.5pt;
            font-weight: bold;
            text-align: center;
            height: 12mm;
            overflow: hidden;
        }

        .cls {
            font-size: 9.5pt;
            font-weight: bold;
            text-align: center;
            height: 7mm;
            overflow: hidden;
        }
        .pen {
            font-size: 10.5pt;
            font-weight: bold;
            text-align: center;
            height: 8mm;
            overflow: hidden;
        }

        .tot-lbl {
            font-size: 9pt;
            font-weight: bold;
            text-align: center;
            line-height: 1.3;
            height: 10mm;
            overflow: hidden;
        }
        .tot {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            height: 16mm;
            overflow: hidden;
        }
        .act {
            font-size: 9.5pt;
            font-weight: bold;
            text-align: right;
            direction: rtl;
            padding: 4px 10px !important;
            line-height: 1.55;
            height: 16mm;
            overflow: hidden;
        }
        .act-line {
            text-align: right;
            direction: rtl;
            unicode-bidi: plaintext;
        }

        .desc-row {
            font-size: 9pt;
            font-weight: bold;
            text-align: right;
            height: 8mm;
            overflow: hidden;
            word-wrap: break-word;
            padding: 3px 6px !important;
        }

        .warn-box {
            border: 1px solid #000;
            margin-top: 0;
            height: 28mm;
            overflow: hidden;
        }
        .warn-t { color: #c00000; font-size: 10pt; font-weight: bold; text-align: center; padding: 4px 4px 1px 4px; }
        .warn-b { font-size: 9pt; font-weight: bold; text-align: center; padding: 1px 8px 2px 8px; line-height: 1.35; }
        .sign-row { font-size: 9pt; font-weight: bold; text-align: center; padding: 1px 4px; }

        .auth-hdr { height: 6.5mm; font-size: 9pt; font-weight: bold; }
        .auth-lbl { background-color: #bdd3e9; font-size: 8.5pt; font-weight: bold; text-align: center; height: 6.5mm; overflow: hidden; }
        .auth-val { font-size: 8.5pt; font-weight: bold; text-align: center; height: 6.5mm; overflow: hidden; }

        .note {
            border: 1px solid #000;
            margin-top: 3px;
            padding: 3px 5px 2px 5px;
            font-size: 8.5pt;
            font-weight: bold;
            text-align: right;
            line-height: 1.3;
            height: 18mm;
            overflow: hidden;
        }
        .dots-line { font-size: 8.5pt; text-align: right; line-height: 1.4; }

        /* Fixed evidence frame — never grows/shrinks with image */
        .ev {
            text-align: center;
            vertical-align: middle !important;
            padding: 2px !important;
            width: 30%;
            height: 78mm;
            min-height: 78mm;
            max-height: 78mm;
            overflow: hidden;
        }
        .ev-img {
            max-width: 88%;
            max-height: 70mm;
            width: auto;
            height: auto;
        }

        .nb { border: none !important; }
        .left { text-align: left; direction: ltr; }
        .right { text-align: right; }

        /* Right panel: one nested table so vertical rules stay straight */
        .right-wrap {
            width: 70%;
            padding: 0 !important;
            vertical-align: top !important;
            height: 78mm;
            max-height: 78mm;
            overflow: hidden;
            /* Avoid double border against .ev and inner .rt */
            border: none !important;
        }
        .rt {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .rt td {
            border: 0.7pt solid #000;
            padding: 2px 3px;
            vertical-align: middle;
            text-align: center;
            font-weight: bold;
            overflow: hidden;
        }
        /*
         * Fixed 3-column rails for the entire right panel.
         * Keeps التكرار vertical rule straight from codes row to penalties.
         * Codes: empty | code1 | code2  (yellow empty + red split)
         */
        .c1 { width: 22%; }
        .c2 { width: 39%; }
        .c3 { width: 39%; }
    </style>
</head>
<body>
@php
    $v1 = $violations[0] ?? ['code' => '', 'description' => '', 'category' => '', 'penalty_display' => '', 'repetition' => '1'];
    $v2 = $violations[1] ?? ['code' => '', 'description' => '', 'category' => '', 'penalty_display' => '', 'repetition' => '1'];
    $logo = $header['company_logo'] ?? null;
    $sideImage = $side_evidence[0] ?? ($primary_evidence ?? null);
    $cat1 = trim((string) ($v1['category'] ?? ''));
    $cat2 = trim((string) ($v2['category'] ?? ''));
@endphp

<div class="page">
    <table class="logo-row" dir="rtl">
        <tr>
            <td style="width:68px; text-align:center;">
                @if ($logo)
                    <img class="logo" src="{{ $logo }}" alt="logo">
                @endif
            </td>
            <td></td>
        </tr>
    </table>

    <table class="t" dir="ltr">
        <tr>
            <td class="bg title" style="width:50%;">Violation Report</td>
            <td class="bg title" style="width:50%;">محضر مخالفة</td>
        </tr>
    </table>

    <table class="t" dir="ltr" style="margin-top:-1px;">
        <tr>
            <td class="val" style="width:18%;">{{ $header['inspection_date'] ?? '' }}</td>
            <td class="lbl" style="width:32%;">تاريخ زيارة التفتيش</td>
            <td class="val" style="width:25%;">{{ $header['contractor_name'] ?? '' }}</td>
            <td class="lbl" style="width:25%;">اسم المقاول</td>
        </tr>
        <tr>
            <td class="val">{{ $header['inspection_time'] ?? '' }}</td>
            <td class="lbl">وقت الزيارة</td>
            <td class="val" style="font-size:8pt;">{{ $header['project_name'] ?? '' }}</td>
            <td class="lbl">اسم المشروع</td>
        </tr>
        <tr>
            <td class="val">{{ $header['violation_location'] ?? '' }}</td>
            <td class="lbl">مكان المخالفة</td>
            <td class="val">{{ $header['work_order'] ?? '' }}</td>
            <td class="lbl">امر العمل / النوع</td>
        </tr>
        <tr>
            <td class="val">{{ $header['contractor_safety_rep'] ?? '' }}</td>
            <td class="lbl" style="font-size:8pt;">ممثل السلامة لدى المقاول بالموقع</td>
            <td class="val">{{ $header['project_manager'] ?? '' }}</td>
            <td class="lbl">مدير المشروع</td>
        </tr>
        <tr>
            <td class="sec" colspan="4">
                <table class="nb" style="width:100%;">
                    <tr>
                        <td class="nb left" style="width:58%; font-size:9.5pt; font-weight:bold;">
                            Details of the violation &nbsp;&nbsp; ■ Safety &nbsp;&nbsp; □ Other
                        </td>
                        <td class="nb right" style="width:42%; font-size:9.5pt; font-weight:bold;">تفاصيل المخالفة</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Image frame (fixed) + right panel (one nested table = straight vertical rules) --}}
    <table class="t" dir="ltr" style="margin-top:-1px;">
        <tr>
            <td class="ev">
                @if (! empty($sideImage['path']))
                    <img class="ev-img" src="{{ $sideImage['path'] }}" alt="evidence">
                @endif
            </td>
            <td class="right-wrap">
                <table class="rt" dir="ltr">
                    <colgroup>
                        <col style="width:22%;">
                        <col style="width:39%;">
                        <col style="width:39%;">
                    </colgroup>

                    {{-- Codes: yellow empty (c1) + two equal code cells (c2/c3) --}}
                    <tr>
                        <td class="code c1" style="width:22%;">&nbsp;</td>
                        <td class="code c2" style="width:39%;">{{ $v1['code'] ?? '' }}</td>
                        <td class="code c3" style="width:39%;">{{ $v2['code'] ?? '' }}</td>
                    </tr>

                    {{-- Violation 1 --}}
                    <tr>
                        <td class="bg hdr-cell c1">
                            <div class="rep-ar">التكرار</div>
                            <div class="rep-en">Repetition</div>
                        </td>
                        <td class="bg hdr-cell" colspan="2">
                            <div class="ref-ar">حسب جدول تصنيف المخالفات، رقم ونص المخالفة</div>
                            <div class="ref-en">Violation Reference and statement</div>
                            <div class="ref-en">(as per violation Classification table)</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="repv c1">{{ $v1['repetition'] ?? '1' }}</td>
                        <td class="desc" colspan="2">{{ $v1['description'] ?? '' }}</td>
                    </tr>

                    {{-- Violation 2 --}}
                    <tr>
                        <td class="bg hdr-cell c1">
                            <div class="rep-ar">التكرار</div>
                            <div class="rep-en">Repetition</div>
                        </td>
                        <td class="bg hdr-cell" colspan="2">
                            <div class="ref-ar">حسب جدول تصنيف المخالفات، رقم ونص المخالفة</div>
                            <div class="ref-en">Violation Reference and statement</div>
                            <div class="ref-en">(as per violation Classification table)</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="repv c1">{{ $v2['repetition'] ?? '1' }}</td>
                        <td class="desc" colspan="2">{{ $v2['description'] ?? '' }}</td>
                    </tr>

                    {{-- Classification on same 3-col rails (no nested table) --}}
                    <tr>
                        <td class="bg cls c1">التصنيف</td>
                        <td class="bg cls c2">( {{ $cat1 }} ) التصنيف</td>
                        <td class="bg cls c3">( {{ $cat2 }} ) التصنيف</td>
                    </tr>
                    <tr>
                        <td class="c1" style="height:8mm;">&nbsp;</td>
                        <td class="pen c2">{{ $v1['penalty_display'] ?? '' }}</td>
                        <td class="pen c3">{{ $v2['penalty_display'] ?? '' }}</td>
                    </tr>

                    {{-- Total + الجزاء: full-width nested (different split by design, like original) --}}
                    <tr>
                        <td colspan="3" style="padding:0;">
                            <table class="rt" dir="ltr">
                                <tr>
                                    <td class="bg tot-lbl" style="width:38%;">
                                        إجمالي قيمة الغرامة<br>حسب التصنيف والتكرار
                                    </td>
                                    <td class="bg tot-lbl" style="width:62%;">
                                        الجزاء حسب التصنيف والتكرار
                                    </td>
                                </tr>
                                <tr>
                                    <td class="tot">{{ $total_penalty_display }}</td>
                                    <td class="act">
                                        @for ($i = 0; $i < 3; $i++)
                                            @if (! empty($actions[$i]))
                                                <div class="act-line">{{ $i + 1 }}- {{ $actions[$i] }}</div>
                                            @else
                                                <div class="act-line">&nbsp;</div>
                                            @endif
                                        @endfor
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="t" dir="rtl" style="margin-top:-1px;">
        <tr>
            <td class="desc-row right">
                وصف المخالفة: {{ $description }}
            </td>
        </tr>
    </table>

    <div class="warn-box">
        <div class="warn-t">تنبيه</div>
        <div class="warn-b">
            ننبه بتصحيح الملاحظات خلال المدة (5) أيام، وفي حال عدم التصحيح سيتم تطبيق التكرار على المخالفة القائمة.
        </div>
        <div class="sign-row">الاسم : ..............................................................................................................</div>
        <div class="sign-row">التوقيع : ............................................................................................................</div>
    </div>

    <table class="t" dir="ltr" style="margin-top:3px;">
        <tr>
            <td class="bg left auth-hdr" style="width:50%; padding:3px 5px;">Authentication :</td>
            <td class="bg right auth-hdr" style="width:50%; padding:3px 5px;">المصادقة :</td>
        </tr>
    </table>

    <table class="t" dir="rtl" style="margin-top:-1px; table-layout:fixed;">
        <colgroup>
            <col style="width:25%;">
            <col style="width:25%;">
            <col style="width:25%;">
            <col style="width:25%;">
        </colgroup>
        <tr>
            <td class="auth-lbl" style="width:25%;">محرر المحضر</td>
            <td class="auth-val" style="width:25%;">{{ $header['preparer_name'] ?? '' }}&nbsp;</td>
            <td class="auth-lbl" style="width:25%;">مستلم المحضر</td>
            <td class="auth-val" style="width:25%;">&nbsp;</td>
        </tr>
        <tr>
            <td class="auth-lbl">الرقم الوظيفي</td>
            <td class="auth-val">{{ $header['preparer_job_code'] ?? '' }}&nbsp;</td>
            <td class="auth-lbl">رقم الإقامة</td>
            <td class="auth-val">&nbsp;</td>
        </tr>
        <tr>
            <td class="auth-lbl">التاريخ</td>
            <td class="auth-val">{{ $header['inspection_date'] ?? '' }}&nbsp;</td>
            <td class="auth-lbl">التاريخ</td>
            <td class="auth-val">&nbsp;</td>
        </tr>
        <tr>
            <td class="auth-lbl">التوقيع</td>
            <td class="auth-val">{{ $header['preparer_name'] ?? '' }}&nbsp;</td>
            <td class="auth-lbl">التوقيع</td>
            <td class="auth-val">&nbsp;</td>
        </tr>
        <tr>
            <td class="auth-lbl" style="font-size:7.5pt;">مدير إدارة التخطيط والإنشاءات</td>
            <td class="auth-val">{{ $header['planning_manager_name'] ?? '' }}&nbsp;</td>
            <td class="auth-lbl">التوقيع</td>
            <td class="auth-val">&nbsp;</td>
        </tr>
    </table>

    <div class="note">
        ملاحظة : في حالة امتناع ممثل المقاول (مدير وممثل السلامة) (بعد توقيع استلام المحضر أو تفنيده) يتم إفادة مدير الإدارة لأخذ ما يناسب من التوصيات على المخالفة.
        <div class="dots-line">................................................................................................................................................................................................................</div>
        <div class="dots-line">................................................................................................................................................................................................................</div>
    </div>
</div>
</body>
</html>
