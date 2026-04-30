<?php

declare(strict_types=1);

namespace Modules\Reports\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Modules\Reports\DTO\ReportWizardConfigDTO;
use Modules\Reports\Enums\ReportEnums;
use Modules\Reports\Models\Report;
use Modules\Reports\Services\ReportLookupService;

/**
 * One sheet per selected report type (attendance / leaves / salaries / etc.).
 * Each row is an employee + the numeric facts extracted for that type.
 */
class ReportTypeSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function __construct(
        private string                $type,
        private Report                $report,
        private ReportWizardConfigDTO $config,
        private Collection            $employees,
        /** @var array<string, array<string,mixed>> */
        private array                 $data,
        private ReportLookupService   $lookups,
    ) {
    }

    public function title(): string
    {
        // Excel sheet names are limited to 31 characters.
        return substr($this->humanTitle(), 0, 31);
    }

    public function collection(): Collection
    {
        return $this->employees;
    }

    public function headings(): array
    {
        return array_merge($this->employeeHeadings(), $this->metricHeadings());
    }

    /**
     * @param mixed $row
     */
    public function map($row): array
    {
        $key     = (string) ($row->global_id ?? '');
        $metrics = $this->data[$key] ?? [];

        $base = [
            $row->name ?? null,
            $row->email ?? null,
            optional($row->country)->name ?? null,
            optional($row->userProfessionalData)->job_code ?? null,
            optional($row->jobTitle)->name ?? null,
        ];

        $metricKeys = $this->metricKeys();
        $tail       = [];
        foreach ($metricKeys as $k) {
            $tail[] = $metrics[$k] ?? 0;
        }

        return array_merge($base, $tail);
    }

    private function humanTitle(): string
    {
        $lang    = $this->config->step1->reportLanguage;
        $catalog = $this->lookups->reportTypes();
        foreach ($catalog as $entry) {
            if ($entry['id'] === $this->type) {
                return $entry['label'][$lang] ?? $entry['label']['en'] ?? $this->type;
            }
        }
        return $this->type;
    }

    /** @return string[] */
    private function employeeHeadings(): array
    {
        return ['Employee Name', 'Email', 'Nationality', 'Employee Code', 'Job Title'];
    }

    /** @return string[] */
    private function metricHeadings(): array
    {
        return match ($this->type) {
            ReportEnums::REPORT_TYPE_ATTENDANCE_ABSENCE => ['Present Days', 'Absent Days', 'Delay (min)', 'Overtime (min)', 'Early Leave (min)'],
            ReportEnums::REPORT_TYPE_LEAVES              => ['Taken Leaves', 'Unpaid Leaves', 'Sick Leaves'],
            ReportEnums::REPORT_TYPE_OVERTIME            => ['Overtime (min)', 'Overtime Days'],
            ReportEnums::REPORT_TYPE_MONTHLY_PERFORMANCE => ['Performance Score'],
            ReportEnums::REPORT_TYPE_SALARIES            => array_merge(['Net'], $this->config->step4->salaryComponentIds),
            ReportEnums::REPORT_TYPE_LATENESS            => ['Late Count', 'Late Minutes'],
            ReportEnums::REPORT_TYPE_DEDUCTIONS          => array_merge(['Total Deductions'], $this->config->step4->deductionIds),
            ReportEnums::REPORT_TYPE_BRANCHES_COMPARISON => ['Headcount', 'Present Days', 'Absent Days', 'Delay (min)', 'Overtime (min)'],
            default                                      => [],
        };
    }

    /** @return string[] */
    private function metricKeys(): array
    {
        return match ($this->type) {
            ReportEnums::REPORT_TYPE_ATTENDANCE_ABSENCE => ['present_days', 'absent_days', 'delay_minutes', 'overtime_minutes', 'early_leave_minutes'],
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
}
