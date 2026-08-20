<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: safety, dejavusans, arial, tahoma, sans-serif;
            font-size: 9.5pt;
            color: #1f4e79;
            direction: rtl;
            margin: 0;
            padding: 0;
        }

        .top-meta {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 2px 0;
        }

        .top-meta td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .classification {
            font-size: 8pt;
            color: #548235;
            line-height: 1.3;
            text-align: left;
            direction: ltr;
        }

        .logo-wrap {
            text-align: left;
            direction: ltr;
            padding-bottom: 4px;
        }

        .logo {
            width: 50px;
            height: auto;
        }

        table.main {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #2e75b6;
        }

        table.main td {
            border: 1px solid #2e75b6;
            padding: 5px 4px;
            vertical-align: middle;
            text-align: center;
            color: #1f4e79;
            font-size: 9pt;
            line-height: 1.35;
            background: #ffffff;
        }

        .title-bar {
            background: #bdd7ee !important;
            font-weight: bold;
            font-size: 13pt;
            color: #1f4e79 !important;
            padding: 7px 4px !important;
        }

        .lbl {
            font-weight: bold;
            white-space: nowrap;
            background: #ffffff !important;
        }

        .val {
            font-weight: normal;
            color: #000000 !important;
            background: #ffffff !important;
        }

        .ltr {
            direction: ltr !important;
            unicode-bidi: embed;
            text-align: center;
        }

        .sec {
            background: #bdd7ee !important;
            font-weight: bold;
            font-size: 11pt;
            color: #1f4e79 !important;
            padding: 6px 4px !important;
        }

        .vth {
            background: #ddebf7 !important;
            font-weight: bold;
            font-size: 8.5pt;
            color: #1f4e79 !important;
            padding: 5px 2px !important;
        }

        .vtd {
            height: 17px;
            font-size: 8.5pt;
            color: #000000 !important;
        }

        .notes {
            margin: 7px 2px 8px 2px;
            font-size: 9pt;
            color: #1f4e79;
            text-align: right;
            line-height: 1.5;
            min-height: 12px;
        }

        .auth-title {
            background: #bdd7ee !important;
            font-weight: bold;
            font-size: 11pt;
            color: #1f4e79 !important;
            padding: 6px 4px !important;
        }

        .auth-lbl {
            font-weight: bold;
            font-size: 8.5pt;
            white-space: nowrap;
        }

        .auth-val {
            color: #000000 !important;
            font-size: 9pt;
        }

        .mgr {
            background: #fce4d6 !important;
            font-weight: bold;
            text-align: right !important;
            padding: 7px 8px !important;
            color: #1f4e79 !important;
        }

        .refuse-note {
            margin: 7px 2px 0 2px;
            font-size: 9pt;
            color: #1f4e79;
            text-align: right;
        }
    </style>
</head>
<body>
@php
    $h = $header ?? [];
    $e = static function ($v) {
        if ($v === null || $v === '') {
            return '&nbsp;';
        }

        return e((string) $v);
    };
@endphp

<table class="top-meta" dir="ltr">
    <tr>
        <td style="width:60px;" class="logo-wrap">
            @if (! empty($h['logo']))
                <img class="logo" src="{{ $h['logo'] }}" alt="se">
            @endif
        </td>
        <td class="classification">Public Internal - عام (داخلي)</td>
    </tr>
</table>

<table class="main" dir="rtl">
    <tr>
        <td class="title-bar" colspan="12" bgcolor="#bdd7ee">نموذج محضر مخالفة سلامة</td>
    </tr>

    <tr>
        <td class="lbl" colspan="3" style="width:22%;">رقم محضر المخالفة</td>
        <td class="val ltr" colspan="9" style="width:78%;">{!! $e($h['report_number'] ?? '') !!}</td>
    </tr>

    <tr>
        <td class="lbl" colspan="2" style="width:12%;">الإدارة</td>
        <td class="val" colspan="1" style="width:13%;">{!! $e($h['department'] ?? '') !!}</td>
        <td class="lbl" colspan="1" style="width:10%;">الدائرة</td>
        <td class="val" colspan="2" style="width:15%;">{!! $e($h['circle'] ?? '') !!}</td>
        <td class="lbl" colspan="1" style="width:10%;">مشاريع</td>
        <td class="val" colspan="2" style="width:15%;">{!! $e($h['projects'] ?? '') !!}</td>
        <td class="lbl" colspan="1" style="width:10%;">المكتب</td>
        <td class="val" colspan="2" style="width:15%;">{!! $e($h['office'] ?? '') !!}</td>
    </tr>

    <tr>
        <td class="lbl" colspan="3">اسم المقاول</td>
        <td class="val" colspan="4">{!! $e($h['contractor_name'] ?? '') !!}</td>
        <td class="lbl" colspan="2">رقم العقد للمقاول</td>
        <td class="val ltr" colspan="3">{!! $e($h['contractor_contract_number'] ?? '') !!}</td>
    </tr>

    <tr>
        <td class="lbl" colspan="2">موقع الزيارة</td>
        <td class="val" colspan="3">{!! $e($h['visit_location'] ?? '') !!}</td>
        <td class="lbl" colspan="1">الوقت</td>
        <td class="val ltr" colspan="2">{!! $e($h['visit_time'] ?? '') !!}</td>
        <td class="lbl" colspan="1">التاريخ</td>
        <td class="val ltr" colspan="3">{!! $e($h['visit_date'] ?? '') !!}</td>
    </tr>

    <tr>
        <td class="lbl" colspan="3">اسم مصدر تصريح العمل</td>
        <td class="val" colspan="3">{!! $e($h['permit_source'] ?? '') !!}</td>
        <td class="lbl" colspan="3">اسم مستلم تصريح العمل</td>
        <td class="val" colspan="3">{!! $e($h['permit_recipient'] ?? '') !!}</td>
    </tr>

    <tr>
        <td class="lbl" colspan="3">اسم ممثل السلامة للمقاول</td>
        <td class="val" colspan="3">{!! $e($h['contractor_safety_rep'] ?? '') !!}</td>
        <td class="lbl" colspan="2">رقم امر العمل</td>
        <td class="val ltr" colspan="4">{!! $e($h['work_order'] ?? '') !!}</td>
    </tr>

    <tr>
        <td class="sec" colspan="12" bgcolor="#bdd7ee">تفاصيل المخالفات</td>
    </tr>

    <tr>
        <td class="vth" style="width:5%;" bgcolor="#ddebf7">م</td>
        <td class="vth" colspan="2" style="width:16%;" bgcolor="#ddebf7">رقم بند المخالفة بالعقد</td>
        <td class="vth" colspan="5" style="width:41%;" bgcolor="#ddebf7">بند المخالفة</td>
        <td class="vth" style="width:10%;" bgcolor="#ddebf7">التكرار</td>
        <td class="vth" colspan="2" style="width:14%;" bgcolor="#ddebf7">قيمة المخالفة</td>
        <td class="vth" style="width:14%;" bgcolor="#ddebf7">الاجمالي</td>
    </tr>

    @foreach ($violation_rows as $row)
        <tr>
            <td class="vtd">{!! $e($row['serial'] ?? '') !!}</td>
            <td class="vtd ltr" colspan="2">{!! $e($row['code'] ?? '') !!}</td>
            <td class="vtd" colspan="5" style="text-align:right;">{!! $e($row['description'] ?? '') !!}</td>
            <td class="vtd">{!! $e($row['repetition'] ?? '') !!}</td>
            <td class="vtd ltr" colspan="2">{!! $e($row['value_display'] ?? '') !!}</td>
            <td class="vtd ltr">{!! $e($row['total_display'] ?? '') !!}</td>
        </tr>
    @endforeach

    <tr>
        <td class="vtd">&nbsp;</td>
        <td class="vtd" colspan="2">&nbsp;</td>
        <td class="vtd" colspan="5" style="font-weight:bold; color:#1f4e79;">إجمالي قيمة المخالفات</td>
        <td class="vtd">&nbsp;</td>
        <td class="vtd" colspan="2">&nbsp;</td>
        <td class="vtd ltr" style="font-weight:bold;">{!! $e($grand_total_display ?? '') !!}</td>
    </tr>
</table>

<div class="notes">{!! $e($h['notes'] ?? '') !!}</div>

<table class="main" dir="rtl" style="margin-top:2px;">
    <tr>
        <td class="auth-title" colspan="6" bgcolor="#bdd7ee">المصادقة</td>
    </tr>

    <tr>
        <td class="auth-lbl" style="width:18%;">اسم محرر المحضر</td>
        <td class="auth-val" style="width:16%;">{!! $e($h['preparer_name'] ?? '') !!}</td>
        <td class="auth-lbl" style="width:14%;">الرقم الوظيفي</td>
        <td class="auth-val" style="width:16%;">{!! $e($h['preparer_job_code'] ?? '') !!}</td>
        <td class="auth-lbl" style="width:12%;">التوقيع</td>
        <td class="auth-val" style="width:24%;">{!! $e($h['preparer_signature'] ?? '') !!}</td>
    </tr>

    <tr>
        <td class="auth-lbl">اسم مستلم المحضر للمقاول</td>
        <td class="auth-val">{!! $e($h['recipient_name'] ?? '') !!}</td>
        <td class="auth-lbl">الرقم الوظيفي/ الاقامة</td>
        <td class="auth-val">{!! $e($h['recipient_job_or_iqama'] ?? '') !!}</td>
        <td class="auth-lbl">التوقيع</td>
        <td class="auth-val">{!! $e($h['recipient_signature'] ?? '') !!}</td>
    </tr>

    <tr>
        <td class="auth-lbl">الوقت</td>
        <td class="auth-val ltr">{!! $e($h['auth_time'] ?? '') !!}</td>
        <td class="auth-lbl">التاريخ</td>
        <td class="auth-val ltr" colspan="3">{!! $e($h['auth_date'] ?? '') !!}</td>
    </tr>

    <tr>
        <td class="mgr" colspan="6" bgcolor="#fce4d6">اعتماد مدير الإدارة</td>
    </tr>
</table>

<div class="refuse-note">في حال امتناع ممثل المقاول عن التوقيع يتم ذكر السبب</div>

</body>
</html>
