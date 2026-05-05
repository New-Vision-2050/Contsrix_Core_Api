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
        @endphp
        <h2>{{ $label }}</h2>
        @if ($employees->isEmpty())
            <p>{{ $lang === 'ar' ? 'لا توجد بيانات.' : 'No data.' }}</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>{{ $lang === 'ar' ? 'الموظف' : 'Employee' }}</th>
                        @foreach (($rows[array_key_first($rows)] ?? []) as $metric => $_)
                            <th>{{ $metric }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($employees as $emp)
                        @php $row = $rows[(string) $emp->global_id] ?? []; @endphp
                        <tr>
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
