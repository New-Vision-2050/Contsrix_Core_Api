<?php

declare(strict_types=1);

namespace Modules\Reports\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
 * Wiring details
 * --------------
 * - Attendance / lateness / overtime / branches_comparison are sourced from the
 *   local `attendances` table (see Modules\Attendance\Models\Attendance), joined
 *   to `users` so `Attendance.user_id` (= User.id) can be folded back to
 *   `CompanyUser.global_id` via `users.global_company_user_id`.
 * - Leaves come from `leave_requests` + `leave_types` (paid/unpaid via
 *   `leave_types.is_paid`, sick detected from the translated name).
 * - Period filtering uses `attendances.business_date` (a date column) and the
 *   leave-request overlap is computed in PHP for clarity.
 *
 * Modules that don't yet ship a queryable source in this service (salaries,
 * deductions, monthly_performance) keep deterministic zero stubs so the
 * generation pipeline never fails — wire them up when the corresponding
 * domain modules expose the data they need.
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

    // -----------------------------------------------------------------------
    // Attendance (table: `attendances`)
    // -----------------------------------------------------------------------

    /**
     * Aggregates attendance metrics for every employee in the period.
     *
     * SQL: counts distinct `business_date` per status flag, sums delay /
     * overtime / early-leave minutes. Joins `users` so the rows come back
     * keyed by `users.global_company_user_id` (= CompanyUser.global_id),
     * which is what the rendering layer expects.
     */
    protected function extractAttendance(Report $report, ReportWizardConfigDTO $config, Collection $employees): array
    {
        $base = $this->emptyMetrics($employees, [
            'present_days'        => 0,
            'absent_days'         => 0,
            'delay_minutes'       => 0,
            'overtime_minutes'    => 0,
            'early_leave_minutes' => 0,
        ]);

        $globalIds = $this->globalIds($employees);
        if ($globalIds === []) {
            return $base;
        }

        [$start, $end] = $this->periodBounds($report);

        $rows = DB::table('attendances as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->select(
                'u.global_company_user_id as global_id',
                DB::raw("COUNT(DISTINCT CASE WHEN a.is_absent = 0 AND a.is_holiday = 0 AND a.clock_in_time IS NOT NULL THEN a.business_date END) as present_days"),
                DB::raw("COUNT(DISTINCT CASE WHEN a.is_absent = 1 THEN a.business_date END) as absent_days"),
                DB::raw("COALESCE(SUM(CASE WHEN a.is_late = 1 THEN a.late_minutes ELSE 0 END), 0) as delay_minutes"),
                DB::raw("COALESCE(SUM(CAST(a.overtime_hours AS DECIMAL(10,2))) * 60, 0) as overtime_minutes"),
                DB::raw("COALESCE(SUM(CASE WHEN a.is_early_departure = 1 THEN a.early_departure_minutes ELSE 0 END), 0) as early_leave_minutes")
            )
            ->where('a.company_id', tenant('id'))
            ->whereBetween('a.business_date', [$start, $end])
            ->whereIn('u.global_company_user_id', $globalIds)
            ->groupBy('u.global_company_user_id')
            ->get();

        foreach ($rows as $r) {
            $base[(string) $r->global_id] = [
                'present_days'        => (int) $r->present_days,
                'absent_days'         => (int) $r->absent_days,
                'delay_minutes'       => (int) $r->delay_minutes,
                'overtime_minutes'    => (int) round((float) $r->overtime_minutes),
                'early_leave_minutes' => (int) $r->early_leave_minutes,
            ];
        }

        return $base;
    }

    /**
     * Lateness: number of late attendance entries + total accumulated late minutes.
     */
    protected function extractLateness(Report $report, ReportWizardConfigDTO $config, Collection $employees): array
    {
        $base = $this->emptyMetrics($employees, [
            'late_count'   => 0,
            'late_minutes' => 0,
        ]);

        $globalIds = $this->globalIds($employees);
        if ($globalIds === []) {
            return $base;
        }

        [$start, $end] = $this->periodBounds($report);

        $rows = DB::table('attendances as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->select(
                'u.global_company_user_id as global_id',
                DB::raw("COALESCE(SUM(CASE WHEN a.is_late = 1 THEN 1 ELSE 0 END), 0) as late_count"),
                DB::raw("COALESCE(SUM(CASE WHEN a.is_late = 1 THEN a.late_minutes ELSE 0 END), 0) as late_minutes")
            )
            ->where('a.company_id', tenant('id'))
            ->whereBetween('a.business_date', [$start, $end])
            ->whereIn('u.global_company_user_id', $globalIds)
            ->groupBy('u.global_company_user_id')
            ->get();

        foreach ($rows as $r) {
            $base[(string) $r->global_id] = [
                'late_count'   => (int) $r->late_count,
                'late_minutes' => (int) $r->late_minutes,
            ];
        }

        return $base;
    }

    /**
     * Overtime: total overtime minutes + count of distinct days with overtime.
     */
    protected function extractOvertime(Report $report, ReportWizardConfigDTO $config, Collection $employees): array
    {
        $base = $this->emptyMetrics($employees, [
            'overtime_minutes' => 0,
            'overtime_days'    => 0,
        ]);

        $globalIds = $this->globalIds($employees);
        if ($globalIds === []) {
            return $base;
        }

        [$start, $end] = $this->periodBounds($report);

        $rows = DB::table('attendances as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->select(
                'u.global_company_user_id as global_id',
                DB::raw("COALESCE(SUM(CAST(a.overtime_hours AS DECIMAL(10,2))) * 60, 0) as overtime_minutes"),
                DB::raw("COUNT(DISTINCT CASE WHEN CAST(a.overtime_hours AS DECIMAL(10,2)) > 0 THEN a.business_date END) as overtime_days")
            )
            ->where('a.company_id', tenant('id'))
            ->whereBetween('a.business_date', [$start, $end])
            ->whereIn('u.global_company_user_id', $globalIds)
            ->groupBy('u.global_company_user_id')
            ->get();

        foreach ($rows as $r) {
            $base[(string) $r->global_id] = [
                'overtime_minutes' => (int) round((float) $r->overtime_minutes),
                'overtime_days'    => (int) $r->overtime_days,
            ];
        }

        return $base;
    }

    /**
     * Branches comparison: aggregate attendance metrics per branch (keyed by branch_id).
     *
     * Sources `branch_id` from `user_professional_data` so the comparison
     * follows the same filter set used to build the employee list.
     */
    protected function extractBranchesComparison(Report $report, ReportWizardConfigDTO $config, Collection $employees): array
    {
        // Pre-seed every branch the report touches with a zero row so the
        // sheet stays consistent even if a branch has no attendance yet.
        $base = $employees
            ->groupBy(fn ($e) => (string) ($e->userProfessionalData->branch_id ?? 'unassigned'))
            ->map(fn ($group) => [
                'headcount'        => $group->count(),
                'present_days'     => 0,
                'absent_days'      => 0,
                'delay_minutes'    => 0,
                'overtime_minutes' => 0,
            ])
            ->all();

        $globalIds = $this->globalIds($employees);
        if ($globalIds === []) {
            return $base;
        }

        [$start, $end] = $this->periodBounds($report);

        $rows = DB::table('attendances as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->leftJoin('user_professional_data as upd', function ($join) {
                $join->on('upd.global_id', '=', 'u.global_company_user_id')
                    ->where('upd.company_id', '=', tenant('id'));
            })
            ->select(
                DB::raw("COALESCE(upd.branch_id, 'unassigned') as branch_id"),
                DB::raw("COUNT(DISTINCT CASE WHEN a.is_absent = 0 AND a.is_holiday = 0 AND a.clock_in_time IS NOT NULL THEN a.business_date END) as present_days"),
                DB::raw("COUNT(DISTINCT CASE WHEN a.is_absent = 1 THEN a.business_date END) as absent_days"),
                DB::raw("COALESCE(SUM(CASE WHEN a.is_late = 1 THEN a.late_minutes ELSE 0 END), 0) as delay_minutes"),
                DB::raw("COALESCE(SUM(CAST(a.overtime_hours AS DECIMAL(10,2))) * 60, 0) as overtime_minutes")
            )
            ->where('a.company_id', tenant('id'))
            ->whereBetween('a.business_date', [$start, $end])
            ->whereIn('u.global_company_user_id', $globalIds)
            ->groupBy('upd.branch_id')
            ->get();

        foreach ($rows as $r) {
            $key = (string) $r->branch_id;
            if (!isset($base[$key])) {
                $base[$key] = ['headcount' => 0, 'present_days' => 0, 'absent_days' => 0, 'delay_minutes' => 0, 'overtime_minutes' => 0];
            }
            $base[$key]['present_days']     = (int) $r->present_days;
            $base[$key]['absent_days']      = (int) $r->absent_days;
            $base[$key]['delay_minutes']    = (int) $r->delay_minutes;
            $base[$key]['overtime_minutes'] = (int) round((float) $r->overtime_minutes);
        }

        return $base;
    }

    // -----------------------------------------------------------------------
    // Leaves (tables: `leave_requests`, `leave_types`)
    // -----------------------------------------------------------------------

    /**
     * Leaves: sums approved leave days that overlap the period, bucketed
     * into taken (paid), unpaid, and sick.
     *
     * The day count for each request is the number of days the request
     * covers *inside* the report period (clamped overlap), so leaves that
     * span across the boundary contribute only the overlapping portion.
     */
    protected function extractLeaves(Report $report, ReportWizardConfigDTO $config, Collection $employees): array
    {
        $base = $this->emptyMetrics($employees, [
            'taken_leaves'  => 0,
            'unpaid_leaves' => 0,
            'sick_leaves'   => 0,
        ]);

        $globalIds = $this->globalIds($employees);
        if ($globalIds === []) {
            return $base;
        }

        $periodStart = Carbon::parse($report->period_start)->startOfDay();
        $periodEnd   = Carbon::parse($report->period_end)->endOfDay();

        $rows = DB::table('leave_requests as lr')
            ->join('users as u', 'u.id', '=', 'lr.user_id')
            ->leftJoin('leave_types as lt', 'lt.id', '=', 'lr.leave_type_id')
            ->select(
                'u.global_company_user_id as global_id',
                'lr.start_date',
                'lr.end_date',
                'lt.is_paid',
                'lt.name as leave_type_name'
            )
            ->where('lr.company_id', tenant('id'))
            ->where('lr.status', 'approved')
            ->whereNull('lr.deleted_at')
            ->where('lr.start_date', '<=', $periodEnd->toDateString())
            ->where('lr.end_date', '>=', $periodStart->toDateString())
            ->whereIn('u.global_company_user_id', $globalIds)
            ->get();

        foreach ($rows as $r) {
            $globalId = (string) $r->global_id;
            if (!isset($base[$globalId])) {
                continue;
            }

            $start = Carbon::parse($r->start_date)->startOfDay();
            $end   = Carbon::parse($r->end_date)->endOfDay();

            $overlapStart = $start->greaterThan($periodStart) ? $start : $periodStart;
            $overlapEnd   = $end->lessThan($periodEnd) ? $end : $periodEnd;
            $days         = max(0, $overlapStart->diffInDays($overlapEnd) + 1);

            if ($this->isSickLeave($r->leave_type_name)) {
                $base[$globalId]['sick_leaves'] += $days;
                continue;
            }

            if ((int) ($r->is_paid ?? 1) === 0) {
                $base[$globalId]['unpaid_leaves'] += $days;
                continue;
            }

            $base[$globalId]['taken_leaves'] += $days;
        }

        return $base;
    }

    private function isSickLeave(?string $rawName): bool
    {
        if ($rawName === null || $rawName === '') {
            return false;
        }

        // `leave_types.name` is stored as a JSON column ({"en":"Sick Leave","ar":"إجازة مرضية"}).
        // Match either translation case-insensitively.
        $haystack = mb_strtolower($rawName);

        return str_contains($haystack, 'sick')
            || str_contains($rawName, 'مرضية')
            || str_contains($rawName, 'مرضي');
    }

    // -----------------------------------------------------------------------
    // Stubs (still TODO — require their own modules to be wired)
    // -----------------------------------------------------------------------

    protected function extractMonthlyPerformance(Report $report, ReportWizardConfigDTO $config, Collection $employees): array
    {
        // TODO: hook into the future Performance module once it lands.
        return $this->emptyMetrics($employees, ['performance_score' => 0]);
    }

    protected function extractSalaries(Report $report, ReportWizardConfigDTO $config, Collection $employees): array
    {
        // TODO: join `user_salaries` + selected `salaryComponentIds` from step4
        //       to expose per-component values per employee. Returning a
        //       deterministic zero skeleton keeps the pipeline runnable.
        $components = $config->step4->salaryComponentIds;

        return $employees->mapWithKeys(function ($e) use ($components) {
            $row = ['net' => 0];
            foreach ($components as $c) {
                $row[$c] = 0;
            }
            return [(string) $e->global_id => $row];
        })->all();
    }

    protected function extractDeductions(Report $report, ReportWizardConfigDTO $config, Collection $employees): array
    {
        // TODO: aggregate `salary_deductions` per employee for the period.
        $deductions = $config->step4->deductionIds;

        return $employees->mapWithKeys(function ($e) use ($deductions) {
            $row = ['total_deductions' => 0];
            foreach ($deductions as $d) {
                $row[$d] = 0;
            }
            return [(string) $e->global_id => $row];
        })->all();
    }

    // -----------------------------------------------------------------------
    // Shared helpers
    // -----------------------------------------------------------------------

    /**
     * @param Collection $employees
     * @param array<string,int|float> $template
     * @return array<string,array<string,int|float>>
     */
    private function emptyMetrics(Collection $employees, array $template): array
    {
        return $employees->mapWithKeys(fn ($e) => [(string) $e->global_id => $template])->all();
    }

    /**
     * @return array<int,string>
     */
    private function globalIds(Collection $employees): array
    {
        return $employees->pluck('global_id')->filter()->map(fn ($v) => (string) $v)->unique()->values()->all();
    }

    /**
     * Normalises `Report.period_start` / `period_end` (Carbon-cast on the
     * model) to plain `Y-m-d` strings, which is what `business_date` (a
     * DATE column) expects in MySQL `BETWEEN` predicates.
     *
     * @return array{0:string,1:string}
     */
    private function periodBounds(Report $report): array
    {
        $start = $report->period_start instanceof Carbon
            ? $report->period_start->toDateString()
            : (string) $report->period_start;

        $end = $report->period_end instanceof Carbon
            ? $report->period_end->toDateString()
            : (string) $report->period_end;

        // Strip any trailing time portion ("2026-05-01 00:00:00" → "2026-05-01")
        // so MySQL's date-only column can index-scan the BETWEEN cleanly.
        return [
            substr($start, 0, 10),
            substr($end, 0, 10),
        ];
    }
}
