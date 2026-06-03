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
        body      { font-family: "dejavusans", sans-serif; font-size: 9px; color: #1f2937; }
        h1        { font-size: 15px; margin: 0 0 6px; }
        h2        { font-size: 11px; margin: 12px 0 4px; border-bottom: 2px solid #1e3a5f; padding-bottom: 3px; color: #1e3a5f; }
        table     { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td    { border: 1px solid #d1d5db; padding: 3px 4px; text-align: {{ $align }}; vertical-align: middle; }
        th        { background: #1e3a5f; color: #ffffff; font-weight: 600; font-size: 8px; white-space: nowrap; }
        .meta td  { border: none; padding: 2px 6px; font-size: 10px; }
        .num      { text-align: center; direction: ltr; }
        .tcol     { width: 36px; min-width: 34px; max-width: 40px; }
        .hcol     { width: 44px; min-width: 40px; max-width: 50px; }
        .stats-bar td { border: 1px solid #cbd5e1; padding: 5px 10px; text-align: center; font-weight: 700; font-size: 9px; }
        .s-emp    { background: #e2e8f0; color: #1e293b; }
        .s-pres   { background: #dcfce7; color: #166534; }
        .s-abs    { background: #fee2e2; color: #991b1b; }
        .s-date   { background: #dbeafe; color: #1e40af; }
        .row-alt  { background: #f8fafc; }
        .tot-row  { background: #e8f4ea; font-weight: 700; }
        .tot-row td { border-top: 2px solid #16a34a; }
        .emp-hdr td  { background: #1e3a5f; color: #ffffff; font-size: 10px; font-weight: 700; padding: 6px 8px; border: 1px solid #0f2441; }
        .emp-hdr-sub { font-size: 8px; font-weight: 400; opacity: 0.82; }
    </style>
</head>
<body lang="{{ $lang }}" dir="{{ $dir }}">
    <h1>{{ $reportName }}</h1>
    <table class="meta">
        <tr>
            <td><strong>{{ $lang === 'ar' ? 'الفترة' : 'Period' }}:</strong></td>
            <td>{{ $report->period_start }} — {{ $report->period_end }}</td>
            <td><strong>{{ $lang === 'ar' ? 'عدد الموظفين' : 'Employees' }}:</strong></td>
            <td>{{ $employees->count() }}</td>
        </tr>
        @if ($oneEmployee)
        <tr>
            <td><strong>{{ $lang === 'ar' ? 'الموظف المحدد' : 'Selected Employee' }}:</strong></td>
            <td colspan="3">{{ $oneEmployee }}</td>
        </tr>
        @endif
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
                $totalPresent = 0;
                $totalAbsent  = 0;
                foreach ($employees as $_e) {
                    $_s = is_array($rows) && !str_starts_with((string)$_e->global_id, '__')
                        ? ($rows[(string)$_e->global_id] ?? []) : [];
                    $totalPresent += (int)($_s['present_days'] ?? 0);
                    $totalAbsent  += (int)($_s['absent_days']  ?? 0);
                }
            @endphp

            {{-- Summary stats bar --}}
            <table class="stats-bar">
                <tr>
                    <td class="s-emp">{{ $lang === 'ar' ? 'عدد الموظفين' : 'Total Employees' }}: {{ $employees->count() }}</td>
                    <td class="s-pres">{{ $lang === 'ar' ? 'إجمالي أيام الحضور' : 'Total Present Days' }}: {{ $totalPresent }}</td>
                    <td class="s-abs">{{ $lang === 'ar' ? 'إجمالي أيام الغياب' : 'Total Absent Days' }}: {{ $totalAbsent }}</td>
                    <td class="s-date">{{ $lang === 'ar' ? 'تاريخ اليوم' : 'Report Date' }}: {{ now()->toDateString() }}</td>
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
                @endphp
                @if (!empty($empDaily))
                <table style="margin-bottom:14px;">
                    <thead>
                        {{-- Employee identity row: appears above column headers, repeats on page breaks --}}
                        <tr class="emp-hdr">
                            <td colspan="{{ $empColCount }}" style="padding:7px 10px;">
                                <img src="{{ $empAvatarSrc }}" style="width:32px; height:32px; border-radius:16px; border:2px solid #ffffff; vertical-align:middle;" />
                                <span style="vertical-align:middle; {{ $align === 'right' ? 'margin-right' : 'margin-left' }}:8px; font-size:12px;">{{ $emp->name }}</span>
                                @if ($empBranch)<span class="emp-hdr-sub"> &nbsp;|&nbsp; {{ $empBranch }}</span>@endif
                                @if ($empMgmt)<span class="emp-hdr-sub"> &nbsp;/&nbsp; {{ $empMgmt }}</span>@endif
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
                    </tbody>
                </table>
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
