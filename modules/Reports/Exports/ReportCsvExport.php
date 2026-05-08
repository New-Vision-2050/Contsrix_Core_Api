<?php

declare(strict_types=1);

namespace Modules\Reports\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\Reports\DTO\ReportWizardConfigDTO;
use Modules\Reports\Enums\ReportEnums;
use Modules\Reports\Models\Report;
use Modules\Reports\Services\ReportLookupService;

/**
 * Single-sheet CSV export for a generated Report.
 *
 * Multi-sheet workbooks cannot be expressed in CSV (Maatwebsite/Excel keeps
 * only the first sheet, which used to be the metadata cover — that's the
 * "CSV with HTML-looking content" bug).
 *
 * This export flattens every selected report type into ONE table:
 *   Report Type | Employee | Email | Nationality | Code | Job Title | <metric columns…>
 *
 * Metric columns are the union of all metrics across the selected types so
 * a row from `attendance_absence` and a row from `salaries` can sit in the
 * same sheet without breaking the header.
 */
class ReportCsvExport implements FromArray, WithHeadings
{
    public function __construct(
        private Report                $report,
        private ReportWizardConfigDTO $config,
        private Collection            $employees,
        /** @var array<string, array<string,mixed>> */
        private array                 $sections,
        private ReportLookupService   $lookups,
    ) {
    }

    /** @return string[] */
    public function headings(): array
    {
        return array_merge(
            ['Report Type', 'Employee Name', 'Email', 'Nationality', 'Employee Code', 'Job Title'],
            $this->metricUnion(),
        );
    }

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        $rows         = [];
        $metricUnion  = $this->metricUnion();
        $lang         = $this->config->step1->reportLanguage;
        $catalog      = $this->lookups->reportTypes();

        foreach ($this->config->step1->reportTypeIds as $type) {
            $typeLabel = $this->labelFor($catalog, $type, $lang);
            $data      = $this->sections[$type] ?? [];
            $metrics   = $this->metricKeys($type);

            foreach ($this->employees as $emp) {
                $key   = (string) ($emp->global_id ?? '');
                $bag   = $data[$key] ?? [];

                $base = [
                    $typeLabel,
                    $emp->name ?? null,
                    $emp->email ?? null,
                    optional($emp->country)->name ?? null,
                    optional($emp->userProfessionalData)->job_code ?? null,
                    optional($emp->jobTitle)->name ?? null,
                ];

                $tail = [];
                foreach ($metricUnion as $colKey) {
                    $tail[] = in_array($colKey, $metrics, true) ? ($bag[$colKey] ?? 0) : '';
                }

                $rows[] = array_merge($base, $tail);
            }
        }

        return $rows;
    }

    /**
     * Union of metric column keys across every selected report type, so all
     * rows fit a single header row.
     *
     * @return string[]
     */
    private function metricUnion(): array
    {
        $cols = [];
        foreach ($this->config->step1->reportTypeIds as $type) {
            foreach ($this->metricKeys($type) as $k) {
                $cols[$k] = true;
            }
        }
        return array_keys($cols);
    }

    /** @return string[] */
    private function metricKeys(string $type): array
    {
        return match ($type) {
            ReportEnums::REPORT_TYPE_ATTENDANCE_ABSENCE  => ['present_days', 'absent_days', 'delay_minutes', 'overtime_minutes', 'early_leave_minutes'],
            ReportEnums::REPORT_TYPE_LEAVES              => ['taken_leaves', 'unpaid_leaves', 'sick_leaves'],
            ReportEnums::REPORT_TYPE_OVERTIME            => ['overtime_minutes', 'overtime_days'],
            ReportEnums::REPORT_TYPE_MONTHLY_PERFORMANCE => ['performance_score'],
            ReportEnums::REPORT_TYPE_SALARIES            => array_merge(['net'], $this->config->step4->salaryComponentIds),
            ReportEnums::REPORT_TYPE_LATENESS            => ['late_count', 'late_minutes'],
            ReportEnums::REPORT_TYPE_DEDUCTIONS          => array_merge(['total_deductions'], $this->config->step4->deductionIds),
            ReportEnums::REPORT_TYPE_BRANCHES_COMPARISON => ['headcount', 'present_days', 'absent_days', 'delay_minutes', 'overtime_minutes'],
            default                                      => [],
        };
    }

    /** @param array<int,array{id:string,label:array{ar:string,en:string}}> $catalog */
    private function labelFor(array $catalog, string $id, string $lang): string
    {
        foreach ($catalog as $entry) {
            if ($entry['id'] === $id) {
                return $entry['label'][$lang] ?? $entry['label']['en'] ?? $id;
            }
        }
        return $id;
    }
}
