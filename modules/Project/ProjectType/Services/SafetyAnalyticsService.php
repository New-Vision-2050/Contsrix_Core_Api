<?php

namespace Modules\Project\ProjectType\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Project\ProjectManagement\Models\ProjectContractor;
use Modules\Project\ProjectType\Models\SafetyRecord;
use Modules\Project\ProjectType\Models\Violation;

class SafetyAnalyticsService
{
    /**
     * Average percentage across all completed safety records for a project,
     * plus assignment counts.
     *
     * @return array{
     *     average_percentage: float,
     *     total_evaluations: int,
     *     completed_evaluations: int,
     *     pending_evaluations: int
     * }
     */
    public function overall(string $projectId): array
    {
        $counts = SafetyRecord::query()
            ->where('project_id', $projectId)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("AVG(CASE WHEN status = 'completed' THEN percentage END) as average_percentage")
            ->first();

        $completed = (int) ($counts->completed ?? 0);

        return [
            'average_percentage' => $completed === 0
                ? 0.0
                : round((float) $counts->average_percentage, 2),
            'total_evaluations' => (int) ($counts->total ?? 0),
            'completed_evaluations' => $completed,
            'pending_evaluations' => (int) ($counts->pending ?? 0),
        ];
    }

    /**
     * Count of morphable "locations" on this project where every completed
     * safety record scored 100% (no violations found).
     *
     * @return array{
     *     project_id: string,
     *     compliant_locations: int,
     *     total_locations: int,
     *     is_project_compliant: bool
     * }
     */
    public function compliant(string $projectId): array
    {
        $completed = SafetyRecord::query()
            ->where('project_id', $projectId)
            ->where('status', 'completed')
            ->get(['id', 'morphable_type', 'morphable_id', 'percentage']);

        $byLocation = $completed->groupBy(
            fn (SafetyRecord $record) => $record->morphable_type.'|'.$record->morphable_id
        );

        $compliantLocations = $byLocation
            ->filter(fn (Collection $group) => $group->every(
                fn (SafetyRecord $record) => (float) $record->percentage === 100.0
            ))
            ->count();

        $totalLocations = $byLocation->count();

        return [
            'project_id' => $projectId,
            'compliant_locations' => $compliantLocations,
            'total_locations' => $totalLocations,
            'is_project_compliant' => $totalLocations > 0 && $compliantLocations === $totalLocations,
        ];
    }

    /**
     * Violations found most often on this project, ordered by count desc.
     */
    public function frequentViolations(string $projectId): Collection
    {
        return Violation::query()
            ->select([
                'violations.id',
                'violations.code',
                'violations.description',
                'violations.category',
                'violations.default_weight',
                DB::raw('COUNT(safety_record_violation.id) as count'),
            ])
            ->join('safety_record_violation', 'safety_record_violation.violation_id', '=', 'violations.id')
            ->join('safety_records', 'safety_records.id', '=', 'safety_record_violation.safety_record_id')
            ->where('safety_records.project_id', $projectId)
            ->where('safety_record_violation.status', 'violation_found')
            ->when(
                tenancy()->initialized && ! tenant('is_central_company'),
                fn ($q) => $q->where('safety_records.company_id', tenant('id'))
            )
            ->groupBy(
                'violations.id',
                'violations.code',
                'violations.description',
                'violations.category',
                'violations.default_weight'
            )
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'code' => $row->code,
                'description' => $row->description,
                'category' => $row->category,
                'default_weight' => $row->default_weight,
                'count' => (int) $row->count,
            ]);
    }

    /**
     * Per-violation evaluation breakdown for this project.
     */
    public function violationPerformance(string $projectId): Collection
    {
        $rows = Violation::query()
            ->select([
                'violations.id',
                'violations.code',
                'violations.description',
                'violations.category',
                DB::raw('COUNT(safety_record_violation.id) as total_evaluations'),
                DB::raw("SUM(CASE WHEN safety_record_violation.status = 'violation_found' THEN 1 ELSE 0 END) as violation_found_count"),
                DB::raw("SUM(CASE WHEN safety_record_violation.status = 'no_violation' THEN 1 ELSE 0 END) as no_violation_count"),
                DB::raw("SUM(CASE WHEN safety_record_violation.status = 'not_applicable' THEN 1 ELSE 0 END) as not_applicable_count"),
            ])
            ->join('safety_record_violation', 'safety_record_violation.violation_id', '=', 'violations.id')
            ->join('safety_records', 'safety_records.id', '=', 'safety_record_violation.safety_record_id')
            ->where('safety_records.project_id', $projectId)
            ->when(
                tenancy()->initialized && ! tenant('is_central_company'),
                fn ($q) => $q->where('safety_records.company_id', tenant('id'))
            )
            ->groupBy(
                'violations.id',
                'violations.code',
                'violations.description',
                'violations.category'
            )
            ->orderBy('violations.code')
            ->get();

        return $rows->map(function ($row) {
            $total = (int) $row->total_evaluations;
            $noViolation = (int) $row->no_violation_count;
            $complianceRate = $total === 0
                ? 0.0
                : round(($noViolation / $total) * 100, 2);

            return [
                'id' => $row->id,
                'code' => $row->code,
                'description' => $row->description,
                'category' => $row->category,
                'total_evaluations' => $total,
                'violation_found_count' => (int) $row->violation_found_count,
                'no_violation_count' => $noViolation,
                'not_applicable_count' => (int) $row->not_applicable_count,
                'compliance_rate' => $complianceRate,
            ];
        });
    }

    /**
     * Violation findings grouped by contractor and consultant within the tenant/project.
     */
    public function byContractorConsultant(string $projectId): Collection
    {
        $rows = DB::table('safety_records')
            ->select([
                'safety_records.contractor_id',
                'safety_records.consultant',
                'safety_records.consultant_engineer',
                DB::raw('COUNT(safety_record_violation.id) as violation_count'),
            ])
            ->join('safety_record_violation', 'safety_record_violation.safety_record_id', '=', 'safety_records.id')
            ->where('safety_records.project_id', $projectId)
            ->where('safety_record_violation.status', 'violation_found')
            ->when(
                tenancy()->initialized && ! tenant('is_central_company'),
                fn ($q) => $q->where('safety_records.company_id', tenant('id'))
            )
            ->groupBy(
                'safety_records.contractor_id',
                'safety_records.consultant',
                'safety_records.consultant_engineer'
            )
            ->orderByDesc('violation_count')
            ->get();

        $contractorNames = ProjectContractor::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $rows->pluck('contractor_id')->filter()->unique()->all())
            ->pluck('name', 'id');

        return $rows->map(fn ($row) => [
            'contractor_id' => $row->contractor_id,
            'contractor_name' => $row->contractor_id
                ? ($contractorNames[$row->contractor_id] ?? null)
                : null,
            'consultant' => $row->consultant,
            'consultant_engineer' => $row->consultant_engineer,
            'violation_count' => (int) $row->violation_count,
        ]);
    }

    /**
     * Top N most frequent violations across all companies and projects.
     * Uses the shared DB (single-DB tenancy) without company scoping.
     * Optional inspection_date range filter for weekly reports.
     *
     * @return Collection<int, array{
     *     id: mixed,
     *     code: mixed,
     *     description: mixed,
     *     category: mixed,
     *     default_weight: mixed,
     *     count: int,
     *     percentage: float
     * }>
     */
    public function topViolations(int $limit = 5, ?string $fromDate = null, ?string $toDate = null): Collection
    {
        $rows = $this->globalViolationFoundQuery($fromDate, $toDate)
            ->orderByDesc('count')
            ->limit($limit)
            ->get();

        $total = (int) $this->globalViolationFoundTotal($fromDate, $toDate);

        return collect($rows)->map(fn ($row) => $this->mapViolationFrequencyRow($row, $total));
    }

    /**
     * Frequency % of every found violation across all companies/projects
     * within the inspection_date range (Page 9 of weekly report).
     *
     * @return Collection<int, array{
     *     id: mixed,
     *     code: mixed,
     *     description: mixed,
     *     category: mixed,
     *     default_weight: mixed,
     *     count: int,
     *     percentage: float
     * }>
     */
    public function globalViolationFrequencies(string $fromDate, string $toDate): Collection
    {
        $rows = $this->globalViolationFoundQuery($fromDate, $toDate)
            ->orderByDesc('count')
            ->get();

        $total = (int) $this->globalViolationFoundTotal($fromDate, $toDate);

        return collect($rows)->map(fn ($row) => $this->mapViolationFrequencyRow($row, $total));
    }

    /**
     * Average completed-task percentage per project contractor
     * within the inspection_date range (Page 8).
     * Only contractors that have at least one completed safety record are returned.
     *
     * @return Collection<int, array{
     *     contractor_id: string,
     *     contractor_name: string|null,
     *     percentage: float,
     *     completed_tasks: int
     * }>
     */
    public function contractorCompliance(string $projectId, string $fromDate, string $toDate): Collection
    {
        $rows = SafetyRecord::query()
            ->where('project_id', $projectId)
            ->where('status', 'completed')
            ->whereNotNull('contractor_id')
            ->whereNotNull('inspection_date')
            ->whereDate('inspection_date', '>=', $fromDate)
            ->whereDate('inspection_date', '<=', $toDate)
            ->select([
                'contractor_id',
                DB::raw('AVG(percentage) as average_percentage'),
                DB::raw('COUNT(*) as completed_tasks'),
            ])
            ->groupBy('contractor_id')
            ->orderByDesc('average_percentage')
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $names = ProjectContractor::query()
            ->withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->whereIn('id', $rows->pluck('contractor_id')->all())
            ->pluck('name', 'id');

        return $rows->map(fn ($row) => [
            'contractor_id' => (string) $row->contractor_id,
            'contractor_name' => $names[$row->contractor_id] ?? null,
            'percentage' => round((float) $row->average_percentage, 2),
            'completed_tasks' => (int) $row->completed_tasks,
        ])->values();
    }

    /**
     * Top N violations for a single project contractor within inspection_date range.
     *
     * @return Collection<int, array{
     *     id: mixed,
     *     code: mixed,
     *     description: mixed,
     *     category: mixed,
     *     default_weight: mixed,
     *     count: int,
     *     percentage: float
     * }>
     */
    public function contractorTopViolations(
        string $projectId,
        string $contractorId,
        string $fromDate,
        string $toDate,
        int $limit = 5
    ): Collection {
        $rows = Violation::query()
            ->select([
                'violations.id',
                'violations.code',
                'violations.description',
                'violations.category',
                'violations.default_weight',
                DB::raw('COUNT(safety_record_violation.id) as count'),
            ])
            ->join('safety_record_violation', 'safety_record_violation.violation_id', '=', 'violations.id')
            ->join('safety_records', 'safety_records.id', '=', 'safety_record_violation.safety_record_id')
            ->where('safety_records.project_id', $projectId)
            ->where('safety_records.contractor_id', $contractorId)
            ->where('safety_record_violation.status', 'violation_found')
            ->whereNotNull('safety_records.inspection_date')
            ->whereDate('safety_records.inspection_date', '>=', $fromDate)
            ->whereDate('safety_records.inspection_date', '<=', $toDate)
            ->when(
                tenancy()->initialized && ! tenant('is_central_company'),
                fn ($q) => $q->where('safety_records.company_id', tenant('id'))
            )
            ->groupBy(
                'violations.id',
                'violations.code',
                'violations.description',
                'violations.category',
                'violations.default_weight'
            )
            ->orderByDesc('count')
            ->limit($limit)
            ->get();

        $total = (int) DB::table('safety_record_violation')
            ->join('safety_records', 'safety_records.id', '=', 'safety_record_violation.safety_record_id')
            ->where('safety_records.project_id', $projectId)
            ->where('safety_records.contractor_id', $contractorId)
            ->where('safety_record_violation.status', 'violation_found')
            ->whereNotNull('safety_records.inspection_date')
            ->whereDate('safety_records.inspection_date', '>=', $fromDate)
            ->whereDate('safety_records.inspection_date', '<=', $toDate)
            ->when(
                tenancy()->initialized && ! tenant('is_central_company'),
                fn ($q) => $q->where('safety_records.company_id', tenant('id'))
            )
            ->count();

        return $rows->map(fn ($row) => $this->mapViolationFrequencyRow($row, $total));
    }

    /**
     * Project contractors ordered for weekly report pages (project-scoped only).
     *
     * @return Collection<int, array{id: string, name: string|null}>
     */
    public function projectContractorsForReport(string $projectId): Collection
    {
        return ProjectContractor::query()
            ->withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ProjectContractor $contractor) => [
                'id' => (string) $contractor->id,
                'name' => $contractor->name,
            ])
            ->values();
    }

    /**
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    private function globalViolationFoundQuery(?string $fromDate, ?string $toDate)
    {
        return DB::table('safety_record_violation')
            ->select([
                'violations.id',
                'violations.code',
                'violations.description',
                'violations.category',
                'violations.default_weight',
                DB::raw('COUNT(safety_record_violation.id) as count'),
            ])
            ->join('violations', 'violations.id', '=', 'safety_record_violation.violation_id')
            ->join('safety_records', 'safety_records.id', '=', 'safety_record_violation.safety_record_id')
            ->where('safety_record_violation.status', 'violation_found')
            ->when(
                $fromDate !== null && $toDate !== null,
                function ($q) use ($fromDate, $toDate) {
                    $q->whereNotNull('safety_records.inspection_date')
                        ->whereDate('safety_records.inspection_date', '>=', $fromDate)
                        ->whereDate('safety_records.inspection_date', '<=', $toDate);
                }
            )
            ->groupBy(
                'violations.id',
                'violations.code',
                'violations.description',
                'violations.category',
                'violations.default_weight'
            );
    }

    private function globalViolationFoundTotal(?string $fromDate, ?string $toDate): int
    {
        return (int) DB::table('safety_record_violation')
            ->join('safety_records', 'safety_records.id', '=', 'safety_record_violation.safety_record_id')
            ->where('safety_record_violation.status', 'violation_found')
            ->when(
                $fromDate !== null && $toDate !== null,
                function ($q) use ($fromDate, $toDate) {
                    $q->whereNotNull('safety_records.inspection_date')
                        ->whereDate('safety_records.inspection_date', '>=', $fromDate)
                        ->whereDate('safety_records.inspection_date', '<=', $toDate);
                }
            )
            ->count();
    }

    /**
     * @param  object  $row
     * @return array{
     *     id: mixed,
     *     code: mixed,
     *     description: mixed,
     *     category: mixed,
     *     default_weight: mixed,
     *     count: int,
     *     percentage: float
     * }
     */
    private function mapViolationFrequencyRow(object $row, int $total): array
    {
        $count = (int) $row->count;

        return [
            'id' => $row->id,
            'code' => $row->code,
            'description' => $row->description,
            'category' => $row->category,
            'default_weight' => $row->default_weight,
            'count' => $count,
            'percentage' => $total === 0
                ? 0.0
                : round(($count / $total) * 100, 1),
        ];
    }
}
