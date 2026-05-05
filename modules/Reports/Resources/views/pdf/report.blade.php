@php
    /** @var \Modules\Reports\Models\Report $report */
    /** @var \Modules\Reports\DTO\ReportWizardConfigDTO $config */
    /** @var \Illuminate\Support\Collection $employees */
    /** @var array<string, array<string,mixed>> $sections */
    /** @var \Modules\Reports\Services\ReportLookupService $lookups */
    $lang = $config->step1->reportLanguage;
    $dir  = $lang === 'ar' ? 'rtl' : 'ltr';
    $align = $lang === 'ar' ? 'right' : 'left';
    $reportName = is_array($report->name)
        ? ($report->name[$lang] ?? reset($report->name))
        : (string) $report->name;
    $oneEmployee = $employees->count() === 1 ? optional($employees->first())->name : null;
    $toHoursMinutes = function ($minutes) {
        $total = max(0, (int) $minutes);
        $h = intdiv($total, 60);
        $m = $total % 60;
        return sprintf('%02d:%02d', $h, $m);
    };
@endphp
<!doctype html>
<html lang="{{ $lang }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <title>{{ $reportName }}</title>
    <style>
        /* DejaVu Sans is bundled with mPDF and includes Arabic glyphs. mPDF's
           autoArabic + autoLangToFont will perform contextual letter shaping
           and BiDi reordering automatically when text is tagged with
           lang="ar" / dir="rtl". */
        body  { font-family: "dejavusans", sans-serif; font-size: 11px; color: #1f2937; }
        h1    { font-size: 18px; margin: 0 0 8px; }
        h2    { font-size: 14px; margin: 16px 0 6px; border-bottom: 1px solid #d1d5db; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { border: 1px solid #d1d5db; padding: 4px 6px; text-align: {{ $align }}; vertical-align: top; }
        th    { background: #f3f4f6; font-weight: 600; }
        .meta td { border: none; padding: 2px 6px; }
        .num  { text-align: center; direction: ltr; }
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
            $rows    = $sections[$type] ?? [];
            $daily   = is_array($rows) ? ($rows['__daily'] ?? []) : [];
        @endphp
        <h2>{{ $label }}</h2>
        @if ($employees->isEmpty())
            <p>{{ $lang === 'ar' ? 'لا توجد بيانات.' : 'No data.' }}</p>
        @else
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
                        @if ($type === \Modules\Reports\Enums\ReportEnums::REPORT_TYPE_ATTENDANCE_ABSENCE)
                            <th>present_days</th>
                            <th>absent_days</th>
                            <th>delay_hh_mm</th>
                            <th>overtime_hh_mm</th>
                            <th>early_leave_hh_mm</th>
                        @else
                            @foreach ($sampleRow as $metric => $_)
                                <th>{{ $metric }}</th>
                            @endforeach
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($employees as $emp)
                        @php $row = $rows[(string) $emp->global_id] ?? []; @endphp
                        <tr>
                            <td>{{ $emp->name }}</td>
                            @if ($type === \Modules\Reports\Enums\ReportEnums::REPORT_TYPE_ATTENDANCE_ABSENCE)
                                <td class="num">{{ (int) ($row['present_days'] ?? 0) }}</td>
                                <td class="num">{{ (int) ($row['absent_days'] ?? 0) }}</td>
                                <td class="num">{{ $toHoursMinutes($row['delay_minutes'] ?? 0) }}</td>
                                <td class="num">{{ $toHoursMinutes($row['overtime_minutes'] ?? 0) }}</td>
                                <td class="num">{{ $toHoursMinutes($row['early_leave_minutes'] ?? 0) }}</td>
                            @else
                                @foreach ($row as $v)
                                    <td>{{ $v }}</td>
                                @endforeach
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($type === \Modules\Reports\Enums\ReportEnums::REPORT_TYPE_ATTENDANCE_ABSENCE && !empty($daily))
                <h2>{{ $lang === 'ar' ? 'تفاصيل الحضور اليومية' : 'Daily Attendance Details' }}</h2>
                @foreach ($employees as $emp)
                    @php $empDaily = $daily[(string) $emp->global_id] ?? []; @endphp
                    @if (!empty($empDaily))
                        <p><strong>{{ $emp->name }}</strong></p>
                        <table>
                            <thead>
                                <tr>
                                    <th>{{ $lang === 'ar' ? 'التاريخ' : 'Date' }}</th>
                                    <th>{{ $lang === 'ar' ? 'الحالة' : 'Status' }}</th>
                                    <th>{{ $lang === 'ar' ? 'الدخول' : 'Clock In' }}</th>
                                    <th>{{ $lang === 'ar' ? 'الخروج' : 'Clock Out' }}</th>
                                    <th>{{ $lang === 'ar' ? 'التأخير' : 'Late (HH:MM)' }}</th>
                                    <th>{{ $lang === 'ar' ? 'الإضافي' : 'Overtime (HH:MM)' }}</th>
                                    <th>{{ $lang === 'ar' ? 'الانصراف المبكر' : 'Early Leave (HH:MM)' }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($empDaily as $d)
                                    <tr>
                                        <td class="num">{{ $d['date'] ?? '' }}</td>
                                        <td>{{ $d['day_status'] ?: ($d['status'] ?? '') }}</td>
                                        <td class="num">{{ $config->step3->includeEntryExitTime ? ($d['clock_in_time'] ?? '') : '-' }}</td>
                                        <td class="num">{{ $config->step3->includeEntryExitTime ? ($d['clock_out_time'] ?? '') : '-' }}</td>
                                        <td class="num">{{ $toHoursMinutes($d['late_minutes'] ?? 0) }}</td>
                                        <td class="num">{{ $toHoursMinutes($d['overtime_minutes'] ?? 0) }}</td>
                                        <td class="num">{{ $toHoursMinutes($d['early_leave_minutes'] ?? 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                @endforeach
            @endif
        @endif
    @endforeach
</body>
</html>
