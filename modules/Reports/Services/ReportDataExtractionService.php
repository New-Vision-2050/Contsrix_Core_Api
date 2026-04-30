<?php

declare(strict_types=1);

namespace Modules\Reports\Services;

use Illuminate\Support\Collection;
use Modules\Reports\DTO\ReportWizardConfigDTO;
use Modules\Reports\Enums\ReportEnums;
use Modules\Reports\Models\Report;

/**
 * Pulls the per-section data slices required by each selected report type.
 *
 * Every extractor method MUST return an array keyed by global_id (= CompanyUser.global_id)
 * so the rendering layer can stitch rows together consistently:
 *
 *   [
 *     'attendance_absence' => [
 *         '<global_id>' => ['present_days' => ..., 'absent_days' => ..., ...],
 *         ...
 *     ],
 *     'salaries' => [ ... ],
 *   ]
 *
 * The default implementation ships with deterministic zero-filled stubs so
 * the full pipeline (Job -> Generation -> Excel/PDF rendering) can run end
 * to end against a fresh database. Wire real queries in phase 2 by replacing
 * the individual `extract*` methods with the appropriate repository lookups
 * from the Attendance / Leave / Salary modules.
 */
class ReportDataExtractionService
{
    public function extract(Report $report, ReportWizardConfigDTO $config, Collection $employees): array
    {
        $sections = [];

        foreach ($config->step1->reportTypeIds as $reportType) {
            $sections[$reportType] = match ($reportType) {
                ReportEnums::REPORT_TYPE_ATTENDANCE_ABSENCE  => $this->extractAttendance($report, $config, $employees),
                ReportEnums::REPORT_TYPE_LEAVES              => $this->extractLeaves($report, $config, $employees),
                ReportEnums::REPORT_TYPE_OVERTIME            => $this->extractOvertime($report, $config, $employees),
                ReportEnums::REPORT_TYPE_MONTHLY_PERFORMANCE => $this->extractMonthlyPerformance($report, $config, $employees),
                ReportEnums::REPORT_TYPE_SALARIES            => $this->extractSalaries($report, $config, $employees),
                ReportEnums::REPORT_TYPE_LATENESS            => $this->extractLateness($report, $config, $employees),
                ReportEnums::REPORT_TYPE_DEDUCTIONS          => $this->extractDeductions($report, $config, $employees),
                ReportEnums::REPORT_TYPE_BRANCHES_COMPARISON => $this->extractBranchesComparison($report, $config, $employees),
                default                                      => [],
            };
        }

        return $sections;
    }

    protected function extractAttendance(Report $report, ReportWizardConfigDTO $config, Collection $employees): array
    {
        // TODO: join `attendances` table between period_start..period_end and
        //       aggregate present_days/absent_days/delay_minutes/overtime_minutes
        //       per `global_id`. Returning a deterministic skeleton keeps the
        //       full pipeline runnable today.
        return $employees->mapWithKeys(fn ($e) => [(string) $e->global_id => [
            'present_days'    => 0,
            'absent_days'     => 0,
            'delay_minutes'   => 0,
            'overtime_minutes'=> 0,
            'early_leave_minutes' => 0,
        ]])->all();
    }

    protected function extractLeaves(Report $report, ReportWizardConfigDTO $config, Collection $employees): array
    {
        // TODO: sum approved leave days from the Leave module per employee.
        return $employees->mapWithKeys(fn ($e) => [(string) $e->global_id => [
            'taken_leaves'   => 0,
            'unpaid_leaves'  => 0,
            'sick_leaves'    => 0,
        ]])->all();
    }

    protected function extractOvertime(Report $report, ReportWizardConfigDTO $config, Collection $employees): array
    {
        // TODO: aggregate overtime_minutes from attendances table.
        return $employees->mapWithKeys(fn ($e) => [(string) $e->global_id => [
            'overtime_minutes' => 0,
            'overtime_days'    => 0,
        ]])->all();
    }

    protected function extractMonthlyPerformance(Report $report, ReportWizardConfigDTO $config, Collection $employees): array
    {
        return $employees->mapWithKeys(fn ($e) => [(string) $e->global_id => [
            'performance_score' => 0,
        ]])->all();
    }

    protected function extractSalaries(Report $report, ReportWizardConfigDTO $config, Collection $employees): array
    {
        // TODO: use UserSalary + selected `salaryComponentIds` from step4 to
        //       build per-component values per employee.
        $components = $config->step4->salaryComponentIds;
        return $employees->mapWithKeys(function ($e) use ($components) {
            $row = ['net' => 0];
            foreach ($components as $c) {
                $row[$c] = 0;
            }
            return [(string) $e->global_id => $row];
        })->all();
    }

    protected function extractLateness(Report $report, ReportWizardConfigDTO $config, Collection $employees): array
    {
        return $employees->mapWithKeys(fn ($e) => [(string) $e->global_id => [
            'late_count'      => 0,
            'late_minutes'    => 0,
        ]])->all();
    }

    protected function extractDeductions(Report $report, ReportWizardConfigDTO $config, Collection $employees): array
    {
        $deductions = $config->step4->deductionIds;
        return $employees->mapWithKeys(function ($e) use ($deductions) {
            $row = ['total_deductions' => 0];
            foreach ($deductions as $d) {
                $row[$d] = 0;
            }
            return [(string) $e->global_id => $row];
        })->all();
    }

    protected function extractBranchesComparison(Report $report, ReportWizardConfigDTO $config, Collection $employees): array
    {
        // Aggregated per-branch summary (keyed by branch_id). Uses the employee
        // set to derive per-branch headcount so callers have a valid skeleton.
        return $employees->groupBy(fn ($e) => (string) ($e->userProfessionalData->branch_id ?? 'unassigned'))
            ->map(fn ($group) => [
                'headcount'       => $group->count(),
                'present_days'    => 0,
                'absent_days'     => 0,
                'delay_minutes'   => 0,
                'overtime_minutes'=> 0,
            ])
            ->all();
    }
}
