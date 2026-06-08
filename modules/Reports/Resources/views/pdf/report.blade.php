@php
    /** @var \Modules\Reports\Models\Report $report */
    /** @var \Modules\Reports\DTO\ReportWizardConfigDTO $config */
    /** @var \Illuminate\Support\Collection $employees */
    /** @var array<string, array<string,mixed>> $sections */
    /** @var \Modules\Reports\Services\ReportLookupService $lookups */
    $lang  = $config->step1->reportLanguage;
    $dir   = $lang === 'ar' ? 'rtl' : 'ltr';
    $align = $lang === 'ar' ? 'right' : 'left';

    $reportName  = is_array($report->name)
        ? ($report->name[$lang] ?? reset($report->name))
        : (string) $report->name;
    $oneEmployee = $employees->count() === 1 ? optional($employees->first())->name : null;

    $toHoursMinutes = function ($minutes) {
        $total = max(0, (int) $minutes);
        $h = intdiv($total, 60);
        $m = $total % 60;
        return sprintf('%02d:%02d', $h, $m);
    };
    $workHoursToHM = function ($decimalHours) {
        $total = max(0, (int) round((float) $decimalHours * 60));
        $h = intdiv($total, 60);
        $m = $total % 60;
        return sprintf('%02d:%02d', $h, $m);
    };
    $dayNames = $lang === 'ar'
        ? ['1'=>'الاثنين','2'=>'الثلاثاء','3'=>'الأربعاء','4'=>'الخميس','5'=>'الجمعة','6'=>'السبت','7'=>'الأحد']
        : ['1'=>'Monday','2'=>'Tuesday','3'=>'Wednesday','4'=>'Thursday','5'=>'Friday','6'=>'Saturday','7'=>'Sunday'];
    $avatarPlaceholder = 'data:image/svg+xml;base64,' . base64_encode(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">'
        . '<circle cx="10" cy="10" r="10" fill="#9ca3af"/>'
        . '<circle cx="10" cy="8" r="3.5" fill="#e5e7eb"/>'
        . '<path d="M3 18 Q3 13 10 13 Q17 13 17 18 Z" fill="#e5e7eb"/>'
        . '</svg>'
    );
    $fmtTime = fn ($ts) => ($ts && $ts !== '') ? substr($ts, 11, 5) ?: substr($ts, 0, 5) : '';
@endphp
<!doctype html>
<html lang="{{ $lang }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <title>{{ $reportName }}</title>
    <style>
        body        { font-family: "dejavusans", sans-serif; font-size: 9px; color: #1f2937; margin: 0; }
        h2          { font-size: 11px; margin: 14px 0 6px; border-bottom: 2px solid #1e3a5f; padding-bottom: 4px; color: #1e3a5f; }
        table       { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td      { border: 1px solid #d1d5db; padding: 3px 5px; text-align: {{ $align }}; vertical-align: middle; }
        th          { background: #1e3a5f; color: #ffffff; font-weight: 600; font-size: 8px; white-space: nowrap; }
        .num        { text-align: center; direction: ltr; }
        .tcol       { width: 36px; min-width: 34px; max-width: 40px; }
        .hcol       { width: 44px; min-width: 40px; max-width: 50px; }
        /* ── page-header ── */
        .rpt-header td { border: none; padding: 0; }
        .rpt-hdr-logo  { width: 30%; padding: 14px 16px; vertical-align: middle; }
        .rpt-hdr-title { width: 40%; text-align: center; padding: 14px 8px; vertical-align: middle; }
        .rpt-hdr-meta  { width: 30%; padding: 14px 16px; vertical-align: middle; text-align: {{ $align === 'right' ? 'left' : 'right' }}; }
        /* ── summary stat cards ── */
        .stat-card     { text-align: center; padding: 12px 6px 10px; border: 1px solid #e5e7eb; background: #ffffff; }
        .stat-num      { font-size: 20px; font-weight: 800; line-height: 1; }
        .stat-lbl      { font-size: 8px; color: #6b7280; margin-top: 5px; }
        /* ── data table ── */
        .row-alt   { background: #f8fafc; }
        .tot-row   { background: #eff6ff; font-weight: 700; }
        .tot-row td { border-top: 2px solid #1e3a5f; }
        /* ── employee header ── */
        .emp-hdr td  { background: #1e3a5f; color: #ffffff; font-size: 10px; font-weight: 700; padding: 8px 12px; border: none; }
        .emp-hdr-sub { font-size: 8px; font-weight: 400; opacity: 0.80; }
        .page-break  { page-break-before: always; }
    </style>
</head>
<body lang="{{ $lang }}" dir="{{ $dir }}">
    {{-- ═══════════════ Report Header ═══════════════ --}}
    <table class="rpt-header" style="background:#1e3a5f; margin-bottom:14px;">
        <tr>
            <td class="rpt-hdr-logo">
                @if (!empty($companyLogoUrl))
                    <img src="{{ $companyLogoUrl }}" style="height:38px; width:38px; border-radius:6px; vertical-align:middle; border:2px solid rgba(255,255,255,0.3);" />
                @else
                    <span style="display:inline-block; width:38px; height:38px; background:#2563eb; border-radius:6px; text-align:center; vertical-align:middle; font-size:14px; font-weight:800; color:#fff; line-height:38px;">HR</span>
                @endif
                <span style="vertical-align:middle; {{ $align === 'right' ? 'margin-right' : 'margin-left' }}:8px; font-size:11px; font-weight:700; color:#ffffff;">{{ $companyName }}</span><br/>
                <span style="font-size:8px; color:rgba(255,255,255,0.65); {{ $align === 'right' ? 'margin-right' : 'margin-left' }}:46px;">{{ $lang === 'ar' ? 'نظام الحضور والغياب' : 'Attendance System' }}</span>
            </td>
            <td class="rpt-hdr-title">
                <div style="font-size:15px; font-weight:800; color:#ffffff;">{{ $reportName }}</div>
                <div style="font-size:9px; color:rgba(255,255,255,0.75); margin-top:5px;">{{ substr($report->period_start, 0, 10) }} &mdash; {{ substr($report->period_end, 0, 10) }}</div>
            </td>
            <td class="rpt-hdr-meta">
                <div style="font-size:8px; color:rgba(255,255,255,0.65);">{{ $lang === 'ar' ? 'تاريخ التقرير' : 'Report Date' }}</div>
                <div style="font-size:10px; font-weight:700; color:#ffffff; margin-bottom:6px;">{{ now()->toDateString() }}</div>
                <div style="font-size:8px; color:rgba(255,255,255,0.65);">{{ $lang === 'ar' ? 'إجمالي الموظفين' : 'Total Employees' }}</div>
                <div style="font-size:10px; font-weight:700; color:#ffffff;">{{ $employees->count() }}</div>
            </td>
        </tr>
    </table>

    @foreach ($config->step1->reportTypeIds as $type)
        @php
            $catalog = $lookups->reportTypes();
            $label   = $type;
            foreach ($catalog as $entry) {
                if ($entry['id'] === $type) {
                    $label = $entry['label'][$lang] ?? $entry['label']['en'] ?? $type;
                    break;
                }
            }
            $rows  = $sections[$type] ?? [];
            $daily = is_array($rows) ? ($rows['__daily'] ?? []) : [];
        @endphp
        <h2>{{ $label }}</h2>

        @if ($employees->isEmpty())
            <p>{{ $lang === 'ar' ? 'لا توجد بيانات.' : 'No data.' }}</p>

        @elseif ($type === \Modules\Reports\Enums\ReportEnums::REPORT_TYPE_ATTENDANCE_ABSENCE)
            {{-- ═══ Detailed daily attendance — employee & date grouped with per-session rows ═══ --}}
            @php
                $periodDays = \Carbon\Carbon::parse($report->period_start)
                    ->diffInDays(\Carbon\Carbon::parse($report->period_end)) + 1;
            @endphp

            {{-- Summary stat cards --}}
            <table style="margin-bottom:14px; border-collapse:collapse;">
                <tr>
                    <td class="stat-card" style="border-{{ $align === 'right' ? 'right' : 'left' }}:3px solid #1e3a5f;">
                        <div class="stat-num" style="color:#1e3a5f;">{{ $periodDays }}</div>
                        <div class="stat-lbl">{{ $lang === 'ar' ? 'أيام الفترة' : 'Period Days' }}</div>
                    </td>
                    <td style="width:10px; border:none;"></td>
                    <td class="stat-card" style="border-{{ $align === 'right' ? 'right' : 'left' }}:3px solid #16a34a;">
                        <div class="stat-num" style="color:#16a34a;">{{ $employees->count() }}</div>
                        <div class="stat-lbl">{{ $lang === 'ar' ? 'عدد الموظفين' : 'Employees' }}</div>
                    </td>
                    <td style="width:10px; border:none;"></td>
                    <td class="stat-card" style="border-{{ $align === 'right' ? 'right' : 'left' }}:3px solid #2563eb;">
                        <div class="stat-num" style="color:#2563eb;">{{ substr($report->period_start, 0, 10) }}</div>
                        <div class="stat-lbl">{{ $lang === 'ar' ? 'من' : 'From' }}</div>
                    </td>
                    <td style="width:10px; border:none;"></td>
                    <td class="stat-card" style="border-{{ $align === 'right' ? 'right' : 'left' }}:3px solid #7c3aed;">
                        <div class="stat-num" style="color:#7c3aed;">{{ substr($report->period_end, 0, 10) }}</div>
                        <div class="stat-lbl">{{ $lang === 'ar' ? 'إلى' : 'To' }}</div>
                    </td>
                </tr>
            </table>

            @php
                /* Column visibility — empty array means show all columns */
                $_dc = count($config->step3->attendanceDataTypeIds)
                    ? array_flip($config->step3->attendanceDataTypeIds)
                    : array_flip(\Modules\Reports\Enums\ReportEnums::attendanceDetailColumns());
                $showDay     = isset($_dc[\Modules\Reports\Enums\ReportEnums::ATT_COL_DAY]);
                $showBranch  = isset($_dc[\Modules\Reports\Enums\ReportEnums::ATT_COL_BRANCH]);
                $showMgmt    = isset($_dc[\Modules\Reports\Enums\ReportEnums::ATT_COL_MANAGEMENT]);
                $showOffIn   = isset($_dc[\Modules\Reports\Enums\ReportEnums::ATT_COL_OFFICIAL_IN]);
                $showOffOut  = isset($_dc[\Modules\Reports\Enums\ReportEnums::ATT_COL_OFFICIAL_OUT]);
                $showActIn   = isset($_dc[\Modules\Reports\Enums\ReportEnums::ATT_COL_ACTUAL_IN]);
                $showActOut  = isset($_dc[\Modules\Reports\Enums\ReportEnums::ATT_COL_ACTUAL_OUT]);
                $showTaskIn  = isset($_dc[\Modules\Reports\Enums\ReportEnums::ATT_COL_TASK_IN]);
                $showTaskOut = isset($_dc[\Modules\Reports\Enums\ReportEnums::ATT_COL_TASK_OUT]);
                $showDelay   = isset($_dc[\Modules\Reports\Enums\ReportEnums::ATT_COL_DELAY]);
                $showOT      = isset($_dc[\Modules\Reports\Enums\ReportEnums::ATT_COL_OVERTIME]);
                $showTotal   = isset($_dc[\Modules\Reports\Enums\ReportEnums::ATT_COL_TOTAL_HOURS]);

                // employee_per_page: always 2 (#, date) + up to 12 optional
                $empColCount   = 2 + (int)$showDay + (int)$showBranch + (int)$showMgmt
                               + (int)$showOffIn + (int)$showOffOut
                               + (int)$showActIn + (int)$showActOut
                               + (int)$showTaskIn + (int)$showTaskOut
                               + (int)$showDelay + (int)$showOT + (int)$showTotal;
                $empMetricCols = (int)$showDelay + (int)$showOT + (int)$showTotal;
                $empTotColspan = $empColCount - $empMetricCols;

                // by_day: always 2 (#, employee) + up to 11 optional (no day col)
                $dayColCount   = 2 + (int)$showBranch + (int)$showMgmt
                               + (int)$showOffIn + (int)$showOffOut
                               + (int)$showActIn + (int)$showActOut
                               + (int)$showTaskIn + (int)$showTaskOut
                               + (int)$showDelay + (int)$showOT + (int)$showTotal;
                $dayMetricCols = (int)$showDelay + (int)$showOT + (int)$showTotal;
                $dayTotColspan = $dayColCount - $dayMetricCols;
            @endphp

            @if ($config->step3->displayMode === \Modules\Reports\Enums\ReportEnums::DISPLAY_MODE_BY_DAY)
            {{-- ══════════ BY-DAY view: one table per date, employees as rows ══════════ --}}
            @php
                $byDate = [];
                foreach ($employees as $_emp) {
                    foreach ($daily[(string) $_emp->global_id] ?? [] as $_d) {
                        $_dt = (string) ($_d['date'] ?? '');
                        if ($_dt !== '') {
                            $byDate[$_dt][(string) $_emp->global_id] = ['emp' => $_emp, 'day' => $_d];
                        }
                    }
                }
                ksort($byDate);
            @endphp
            @foreach ($byDate as $dateKey => $dateEmployees)
                @php
                    $dayNum   = date('N', strtotime($dateKey));
                    $dayLabel = $dayNames[(string) $dayNum] ?? '';
                    $dSumDelay   = 0;
                    $dSumOT      = 0;
                    $dSumWorkMin = 0;
                @endphp
                <div @unless($loop->first) class="page-break" @endunless>
                <table style="margin-bottom:2px; border:none;">
                    <tr>
                        <td style="background:#1e3a5f; color:#ffffff; font-size:11px; font-weight:700; padding:6px 10px; border:1px solid #0f2441;">
                            {{ $dateKey }} &nbsp;—&nbsp; {{ $dayLabel }}
                        </td>
                    </tr>
                </table>
                <table style="margin-bottom:14px;">
                    <thead>
                        <tr>
                            <th style="width:18px;">#</th>
                            <th>{{ $lang === 'ar' ? 'الموظف'          : 'Employee' }}</th>
                            @if ($showBranch)<th>{{ $lang === 'ar' ? 'الفرع'           : 'Branch' }}</th>@endif
                            @if ($showMgmt)<th>{{ $lang === 'ar' ? 'الإدارة'         : 'Mgmt' }}</th>@endif
                            @if ($showOffIn)<th class="tcol">{{ $lang === 'ar' ? 'دخول رسمي'  : 'Off.In' }}</th>@endif
                            @if ($showOffOut)<th class="tcol">{{ $lang === 'ar' ? 'خروج رسمي'  : 'Off.Out' }}</th>@endif
                            @if ($showActIn)<th class="tcol">{{ $lang === 'ar' ? 'دخول فعلي'  : 'Act.In' }}</th>@endif
                            @if ($showActOut)<th class="tcol">{{ $lang === 'ar' ? 'خروج فعلي'  : 'Act.Out' }}</th>@endif
                            @if ($showTaskIn)<th class="tcol">{{ $lang === 'ar' ? 'بدء مهمة'   : 'Task In' }}</th>@endif
                            @if ($showTaskOut)<th class="tcol">{{ $lang === 'ar' ? 'نهاية مهمة' : 'Task Out' }}</th>@endif
                            @if ($showDelay)<th class="tcol">{{ $lang === 'ar' ? 'تأخير'      : 'Delay' }}</th>@endif
                            @if ($showOT)<th class="tcol">{{ $lang === 'ar' ? 'إضافي'      : 'Overtime' }}</th>@endif
                            @if ($showTotal)<th class="hcol">{{ $lang === 'ar' ? 'إجمالي'     : 'Total' }}</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @php $dSeq = 0; @endphp
                        @foreach ($employees as $emp)
                            @php $entry = $dateEmployees[(string) $emp->global_id] ?? null; @endphp
                            @if ($entry)
                                @php
                                    $d            = $entry['day'];
                                    $empBranch    = optional(optional($emp->userProfessionalData)->branch)->name     ?? '';
                                    $empMgmt      = optional(optional($emp->userProfessionalData)->management)->name ?? '';
                                    $attSessions  = $d['attendance_sessions'] ?? [];
                                    $taskSessions = $d['task_sessions']       ?? [];
                                    $subRowCount  = max(1, count($attSessions), count($taskSessions));
                                    $rowBg = match($d['display_status'] ?? '') {
                                        'present' => '#f0fdf4',
                                        'absent'  => '#fef2f2',
                                        'holiday' => '#fffbeb',
                                        default   => '#ffffff',
                                    };
                                    $dSumDelay   += (int) ($d['late_minutes']    ?? 0);
                                    $dSumOT      += (int) ($d['overtime_minutes'] ?? 0);
                                    $dSumWorkMin += (int) round((float) ($d['total_work_hours'] ?? 0) * 60);
                                    $dSeq++;
                                @endphp
                                @for ($ri = 0; $ri < $subRowCount; $ri++)
                                    @php
                                        $attRow  = $attSessions[$ri]  ?? null;
                                        $taskRow = $taskSessions[$ri] ?? null;
                                    @endphp
                                    <tr style="background-color:{{ $rowBg }};">
                                        @if ($ri === 0)
                                            <td rowspan="{{ $subRowCount }}" class="num" style="vertical-align:middle;">{{ $dSeq }}</td>
                                            <td rowspan="{{ $subRowCount }}" style="vertical-align:middle;">{{ $emp->name }}</td>
                                            @if ($showBranch)<td rowspan="{{ $subRowCount }}" style="vertical-align:middle;">{{ $empBranch }}</td>@endif
                                            @if ($showMgmt)<td rowspan="{{ $subRowCount }}" style="vertical-align:middle;">{{ $empMgmt }}</td>@endif
                                            @if ($showOffIn)<td rowspan="{{ $subRowCount }}" class="num tcol" style="vertical-align:middle;">{{ $fmtTime($d['start_time']) ?: '-' }}</td>@endif
                                            @if ($showOffOut)<td rowspan="{{ $subRowCount }}" class="num tcol" style="vertical-align:middle;">{{ $fmtTime($d['end_time'])   ?: '-' }}</td>@endif
                                        @endif
                                        @if ($showActIn)<td class="num tcol">{{ $attRow  ? ($fmtTime($attRow['clock_in_time'])  ?: '-') : '' }}</td>@endif
                                        @if ($showActOut)<td class="num tcol">{{ $attRow  ? ($fmtTime($attRow['clock_out_time']) ?: '-') : '' }}</td>@endif
                                        @if ($showTaskIn)<td class="num tcol">{{ $taskRow ? ($taskRow['task_time_in']  ?: '-') : '' }}</td>@endif
                                        @if ($showTaskOut)<td class="num tcol">{{ $taskRow ? ($taskRow['task_time_out'] ?: '-') : '' }}</td>@endif
                                        @if ($ri === 0)
                                            @if ($showDelay)<td rowspan="{{ $subRowCount }}" class="num tcol" style="vertical-align:middle;">{{ $toHoursMinutes($d['late_minutes']    ?? 0) }}</td>@endif
                                            @if ($showOT)<td rowspan="{{ $subRowCount }}" class="num tcol" style="vertical-align:middle;">{{ $toHoursMinutes($d['overtime_minutes'] ?? 0) }}</td>@endif
                                            @if ($showTotal)<td rowspan="{{ $subRowCount }}" class="num hcol" style="vertical-align:middle;">{{ $workHoursToHM($d['total_work_hours']  ?? 0) }}</td>@endif
                                        @endif
                                    </tr>
                                @endfor
                            @endif
                        @endforeach
                        <tr class="tot-row">
                            <td colspan="{{ $dayTotColspan }}" style="text-align:{{ $align }};">{{ $lang === 'ar' ? 'إجمالي اليوم' : 'Day Total' }}</td>
                            @if ($showDelay)<td class="num">{{ $toHoursMinutes($dSumDelay) }}</td>@endif
                            @if ($showOT)<td class="num">{{ $toHoursMinutes($dSumOT) }}</td>@endif
                            @if ($showTotal)<td class="num">{{ $toHoursMinutes($dSumWorkMin) }}</td>@endif
                        </tr>
                    </tbody>
                </table>
                </div>
            @endforeach

            @else
            {{-- ══════════ EMPLOYEE-PER-PAGE view (default): one table per employee ══════════ --}}
            @foreach ($employees as $emp)
                @php
                    $empDaily     = $daily[(string) $emp->global_id] ?? [];
                    $empBranch    = optional(optional($emp->userProfessionalData)->branch)->name     ?? '';
                    $empMgmt      = optional(optional($emp->userProfessionalData)->management)->name ?? '';
                    $empAvatarSrc = $avatarCache[(string) $emp->global_id] ?? $avatarPlaceholder;
                    $sumDelay     = 0;
                    $sumOT        = 0;
                    $sumWorkMin   = 0;
                    $empPresentCount = collect($empDaily)->where('display_status', 'present')->count();
                    $empAbsentCount  = collect($empDaily)->where('display_status', 'absent')->count();
                    $empHolidayCount = collect($empDaily)->where('display_status', 'holiday')->count();
                @endphp
                @if (!empty($empDaily))
                <div @unless($loop->first) class="page-break" @endunless>
                <table style="margin-bottom:14px;">
                    <thead>
                        {{-- Employee identity row: appears above column headers, repeats on page breaks --}}
                        <tr class="emp-hdr">
                            <td colspan="{{ $empColCount }}" style="padding:7px 10px;">
                                <img src="{{ $empAvatarSrc }}" style="width:32px; height:32px; border-radius:16px; border:2px solid #ffffff; vertical-align:middle;" />
                                <span style="vertical-align:middle; {{ $align === 'right' ? 'margin-right' : 'margin-left' }}:8px; font-size:12px;">{{ $emp->name }}</span>
                                @if ($empBranch)<span class="emp-hdr-sub"> &nbsp;|&nbsp; {{ $empBranch }}</span>@endif
                                @if ($empMgmt)<span class="emp-hdr-sub"> &nbsp;/&nbsp; {{ $empMgmt }}</span>@endif
                                <span style="float:{{ $align === 'right' ? 'left' : 'right' }}; font-size:8px; font-weight:400; opacity:0.92; margin-top:4px;">
                                    <span style="background:#dcfce7; color:#166534; border-radius:3px; padding:1px 5px;">{{ $lang === 'ar' ? 'حضور' : 'Present' }}: {{ $empPresentCount }}</span>
                                    &nbsp;
                                    <span style="background:#fee2e2; color:#991b1b; border-radius:3px; padding:1px 5px;">{{ $lang === 'ar' ? 'غياب' : 'Absent' }}: {{ $empAbsentCount }}</span>
                                    &nbsp;
                                    <span style="background:#fef9c3; color:#854d0e; border-radius:3px; padding:1px 5px;">{{ $lang === 'ar' ? 'إجازة' : 'Holiday' }}: {{ $empHolidayCount }}</span>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th style="width:18px;">#</th>
                            <th class="hcol">{{ $lang === 'ar' ? 'التاريخ'        : 'Date' }}</th>
                            @if ($showDay)<th style="width:50px;">{{ $lang === 'ar' ? 'اليوم'   : 'Day' }}</th>@endif
                            @if ($showBranch)<th>{{ $lang === 'ar' ? 'الفرع'                        : 'Branch' }}</th>@endif
                            @if ($showMgmt)<th>{{ $lang === 'ar' ? 'الإدارة'                      : 'Mgmt' }}</th>@endif
                            @if ($showOffIn)<th class="tcol">{{ $lang === 'ar' ? 'دخول رسمي'      : 'Off.In' }}</th>@endif
                            @if ($showOffOut)<th class="tcol">{{ $lang === 'ar' ? 'خروج رسمي'      : 'Off.Out' }}</th>@endif
                            @if ($showActIn)<th class="tcol">{{ $lang === 'ar' ? 'دخول فعلي'      : 'Act.In' }}</th>@endif
                            @if ($showActOut)<th class="tcol">{{ $lang === 'ar' ? 'خروج فعلي'      : 'Act.Out' }}</th>@endif
                            @if ($showTaskIn)<th class="tcol">{{ $lang === 'ar' ? 'بدء مهمة'       : 'Task In' }}</th>@endif
                            @if ($showTaskOut)<th class="tcol">{{ $lang === 'ar' ? 'نهاية مهمة'     : 'Task Out' }}</th>@endif
                            @if ($showDelay)<th class="tcol">{{ $lang === 'ar' ? 'تأخير'          : 'Delay' }}</th>@endif
                            @if ($showOT)<th class="tcol">{{ $lang === 'ar' ? 'إضافي'          : 'Overtime' }}</th>@endif
                            @if ($showTotal)<th class="hcol">{{ $lang === 'ar' ? 'إجمالي ساعات'   : 'Total Hrs' }}</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @php $dateSeq = 0; @endphp
                        @foreach ($empDaily as $d)
                            @php
                                $sumDelay     += (int)   ($d['late_minutes']     ?? 0);
                                $sumOT        += (int)   ($d['overtime_minutes'] ?? 0);
                                $sumWorkMin   += (int)   round((float) ($d['total_work_hours'] ?? 0) * 60);
                                $dateStr       = (string) ($d['date'] ?? '');
                                $dayNum        = $dateStr ? date('N', strtotime($dateStr)) : '';
                                $dayLabel      = $dayNum !== '' ? ($dayNames[(string) $dayNum] ?? '') : '';
                                $rowBg         = match($d['display_status'] ?? '') {
                                    'present' => '#f0fdf4',
                                    'absent'  => '#fef2f2',
                                    'holiday' => '#fffbeb',
                                    default   => '#ffffff',
                                };
                                $subRowCount   = (int) ($d['sub_row_count'] ?? 1);
                                $attSessions   = $d['attendance_sessions'] ?? [];
                                $taskSessions  = $d['task_sessions']       ?? [];
                                $dateSeq++;
                            @endphp
                            @for ($ri = 0; $ri < $subRowCount; $ri++)
                                @php
                                    $attRow  = $attSessions[$ri]  ?? null;
                                    $taskRow = $taskSessions[$ri] ?? null;
                                @endphp
                                <tr style="background-color:{{ $rowBg }};">
                                    @if ($ri === 0)
                                        <td rowspan="{{ $subRowCount }}" class="num" style="vertical-align:middle;">{{ $dateSeq }}</td>
                                        <td rowspan="{{ $subRowCount }}" class="num hcol" style="vertical-align:middle;">{{ $dateStr }}</td>
                                        @if ($showDay)<td rowspan="{{ $subRowCount }}" style="vertical-align:middle;">{{ $dayLabel }}</td>@endif
                                        @if ($showBranch)<td rowspan="{{ $subRowCount }}" style="vertical-align:middle;">{{ $empBranch }}</td>@endif
                                        @if ($showMgmt)<td rowspan="{{ $subRowCount }}" style="vertical-align:middle;">{{ $empMgmt }}</td>@endif
                                        @if ($showOffIn)<td rowspan="{{ $subRowCount }}" class="num tcol" style="vertical-align:middle;">{{ $fmtTime($d['start_time']) ?: '-' }}</td>@endif
                                        @if ($showOffOut)<td rowspan="{{ $subRowCount }}" class="num tcol" style="vertical-align:middle;">{{ $fmtTime($d['end_time'])   ?: '-' }}</td>@endif
                                    @endif
                                    @if ($showActIn)<td class="num tcol">{{ $attRow  ? ($fmtTime($attRow['clock_in_time'])  ?: '-') : '' }}</td>@endif
                                    @if ($showActOut)<td class="num tcol">{{ $attRow  ? ($fmtTime($attRow['clock_out_time']) ?: '-') : '' }}</td>@endif
                                    @if ($showTaskIn)<td class="num tcol">{{ $taskRow ? ($taskRow['task_time_in']  ?: '-') : '' }}</td>@endif
                                    @if ($showTaskOut)<td class="num tcol">{{ $taskRow ? ($taskRow['task_time_out'] ?: '-') : '' }}</td>@endif
                                    @if ($ri === 0)
                                        @if ($showDelay)<td rowspan="{{ $subRowCount }}" class="num tcol" style="vertical-align:middle;">{{ $toHoursMinutes($d['late_minutes']    ?? 0) }}</td>@endif
                                        @if ($showOT)<td rowspan="{{ $subRowCount }}" class="num tcol" style="vertical-align:middle;">{{ $toHoursMinutes($d['overtime_minutes'] ?? 0) }}</td>@endif
                                        @if ($showTotal)<td rowspan="{{ $subRowCount }}" class="num hcol" style="vertical-align:middle;">{{ $workHoursToHM($d['total_work_hours']  ?? 0) }}</td>@endif
                                    @endif
                                </tr>
                            @endfor
                        @endforeach
                        <tr class="tot-row">
                            <td colspan="{{ $empTotColspan }}" style="text-align:{{ $align }};">{{ $lang === 'ar' ? 'الإجمالي' : 'Total' }}</td>
                            @if ($showDelay)<td class="num">{{ $toHoursMinutes($sumDelay) }}</td>@endif
                            @if ($showOT)<td class="num">{{ $toHoursMinutes($sumOT) }}</td>@endif
                            @if ($showTotal)<td class="num">{{ $toHoursMinutes($sumWorkMin) }}</td>@endif
                        </tr>
                        <tr>
                            <td colspan="{{ $empColCount }}" style="padding: 18px 10px 6px; border-top: none; text-align: {{ $align }};">
                                <span style="font-size:9px; font-weight:600;">{{ $lang === 'ar' ? 'التوقيع:' : 'Signature:' }}</span>
                                <span style="display:inline-block; width:180px; border-bottom: 1px solid #374151; margin-{{ $align === 'right' ? 'right' : 'left' }}:8px; vertical-align:bottom;">&nbsp;</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
                @endif
            @endforeach
            @endif

        @else
            {{-- ═══ Other report types: compact summary table ═══ --}}
            @php
                $displayRows = [];
                if (is_array($rows)) {
                    foreach ($rows as $k => $v) {
                        if (!str_starts_with((string) $k, '__')) {
                            $displayRows[$k] = $v;
                        }
                    }
                }
                $sampleRow = $displayRows[array_key_first($displayRows)] ?? [];
            @endphp
            <table>
                <thead>
                    <tr>
                        <th>{{ $lang === 'ar' ? 'الموظف' : 'Employee' }}</th>
                        @foreach ($sampleRow as $metric => $_)
                            <th>{{ $metric }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($employees as $empIdx => $emp)
                        @php $row = $displayRows[(string) $emp->global_id] ?? []; @endphp
                        <tr @if($empIdx % 2 !== 0) class="row-alt" @endif>
                            <td>{{ $emp->name }}</td>
                            @foreach ($row as $v)
                                <td>{{ $v }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach
</body>
</html>
