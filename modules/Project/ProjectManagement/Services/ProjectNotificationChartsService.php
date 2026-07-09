<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Support\Facades\DB;
use Modules\Project\ProjectManagement\DTO\FilterProjectNotificationChartsDTO;
use Modules\Project\ProjectManagement\Models\ProjectNotification;

class ProjectNotificationChartsService
{
    /**
     * Dimensions available for charting / cross-filtering.
     */
    private const DIMENSIONS = [
        'status',
        'notification_type',
        'severity',
        'work_type',
        'contractor_category',
        'project_id',
    ];

    /**
     * Build a base query applying all filters from the DTO, optionally excluding
     * one dimension (used for cross-filtering: when a user selects a value in one
     * chart, the other charts should re-aggregate excluding that dimension's own
     * filter so the user can see what other values are available).
     */
    private function baseQuery(FilterProjectNotificationChartsDTO $dto, ?string $excludeDimension = null): \Illuminate\Database\Eloquent\Builder
    {
        return ProjectNotification::filter($dto->toFilters($excludeDimension));
    }

    /**
     * Get all chart data with cross-filtering support.
     *
     * For each dimension, the aggregation excludes that dimension's own filter
     * so the chart shows the full distribution of that dimension given all other
     * active filters. This enables cross-filtering UX on the frontend.
     */
    public function getChartsData(FilterProjectNotificationChartsDTO $dto): array
    {
        return [
            'status'              => $this->getStatusChart($dto),
            'notification_type'   => $this->getNotificationTypeChart($dto),
            'severity'            => $this->getSeverityChart($dto),
            'work_type'           => $this->getWorkTypeChart($dto),
            'contractor_category' => $this->getContractorCategoryChart($dto),
            'project'             => $this->getProjectChart($dto),
            'assigned_employee'   => $this->getAssignedEmployeeChart($dto),
            'contractor'          => $this->getContractorChart($dto),
            'trend'               => $this->getTrendChart($dto),
        ];
    }

    /**
     * Status distribution (cross-filtered: excludes status filter).
     *
     * in_progress notifications are grouped by the chosen update_site_status.
     * completed notifications are grouped by the chosen end_task_status, where
     * only the "work_completion" key is labelled as "Completed"; other end-task
     * statuses are shown with their own names.
     *
     * cancelled notifications keep the previous pseudo-status split, and
     * pending notifications remain as a single bucket.
     */
    public function getStatusChart(FilterProjectNotificationChartsDTO $dto): array
    {
        $validStatuses = ['pending', 'in_progress', 'completed', 'cancelled'];
        $base = $this->baseQuery($dto, 'status')->whereIn('status', $validStatuses);

        $pendingCount = (clone $base)->where('status', 'pending')->count();

        $inProgressRows = (clone $base)
            ->where('status', 'in_progress')
            ->leftJoin('project_notification_update_site_statuses', 'project_notifications.update_site_status_id', '=', 'project_notification_update_site_statuses.id')
            ->select(
                'project_notification_update_site_statuses.id as status_id',
                'project_notification_update_site_statuses.key as status_key',
                'project_notification_update_site_statuses.name_ar',
                'project_notification_update_site_statuses.name_en',
                DB::raw('count(*) as count'),
            )
            ->groupBy(
                'project_notification_update_site_statuses.id',
                'project_notification_update_site_statuses.key',
                'project_notification_update_site_statuses.name_ar',
                'project_notification_update_site_statuses.name_en',
            )
            ->get();

        $completedRows = (clone $base)
            ->where('status', 'completed')
            ->leftJoin('project_notification_end_task_statuses', 'project_notifications.end_task_status_id', '=', 'project_notification_end_task_statuses.id')
            ->select(
                'project_notification_end_task_statuses.id as status_id',
                'project_notification_end_task_statuses.key as status_key',
                'project_notification_end_task_statuses.name_ar',
                'project_notification_end_task_statuses.name_en',
                DB::raw('count(*) as count'),
            )
            ->groupBy(
                'project_notification_end_task_statuses.id',
                'project_notification_end_task_statuses.key',
                'project_notification_end_task_statuses.name_ar',
                'project_notification_end_task_statuses.name_en',
            )
            ->get();

        $cancelledRows = (clone $base)
            ->where('status', 'cancelled')
            ->select(
                'status',
                DB::raw('location_confirmed_at IS NOT NULL as location_confirmed'),
                DB::raw('confirmation_receive_date IS NOT NULL as received'),
                DB::raw('count(*) as count'),
            )
            ->groupBy('status', 'location_confirmed', 'received')
            ->get();

        $data = [];
        $total = 0;

        if ($pendingCount > 0) {
            $data[] = [
                'code'       => 'pending',
                'label'      => $this->statusLabel('pending'),
                'count'      => (int) $pendingCount,
                'percentage' => 0.0,
            ];
            $total += $pendingCount;
        }

        foreach ($inProgressRows as $row) {
            $count = (int) $row->count;
            $label = $this->resolveStatusName($row->name_ar, $row->name_en, $this->statusLabel('in_progress'));
            $data[] = [
                'code'       => $row->status_key ?? 'in_progress_unknown',
                'label'      => $label,
                'count'      => $count,
                'percentage' => 0.0,
            ];
            $total += $count;
        }

        foreach ($completedRows as $row) {
            $count = (int) $row->count;

            if ($row->status_key === 'work_completion') {
                $code = 'completed';
                $label = $this->statusLabel('completed');
            } else {
                $code = $row->status_key ?? 'completed_unknown';
                $label = $this->resolveStatusName($row->name_ar, $row->name_en, $this->statusLabel('completed'));
            }

            $data[] = [
                'code'       => $code,
                'label'      => $label,
                'count'      => $count,
                'percentage' => 0.0,
            ];
            $total += $count;
        }

        foreach ($cancelledRows as $row) {
            $code = $this->resolveStatusCode('cancelled', (bool) $row->location_confirmed, (bool) $row->received);
            $data[] = [
                'code'       => $code,
                'label'      => $this->statusLabel($code),
                'count'      => (int) $row->count,
                'percentage' => 0.0,
            ];
            $total += $row->count;
        }

        foreach ($data as &$item) {
            $item['percentage'] = $total > 0 ? round(($item['count'] / $total) * 100, 2) : 0.0;
        }

        return ['total' => (int) $total, 'data' => $data];
    }

    /**
     * Notification type distribution (cross-filtered: excludes notification_type filter).
     */
    public function getNotificationTypeChart(FilterProjectNotificationChartsDTO $dto): array
    {
        $rows = $this->baseQuery($dto, 'notification_type')
            ->whereNotNull('notification_type')
            ->select('notification_type', DB::raw('count(*) as count'))
            ->groupBy('notification_type')
            ->pluck('count', 'notification_type')
            ->toArray();

        $total = array_sum($rows);

        $data = [];
        foreach ($rows as $type => $count) {
            $data[] = [
                'code'       => $type,
                'label'      => $type,
                'count'      => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 2) : 0.0,
            ];
        }

        return ['total' => $total, 'data' => $data];
    }

    /**
     * Severity distribution (cross-filtered: excludes severity filter).
     */
    public function getSeverityChart(FilterProjectNotificationChartsDTO $dto): array
    {
        $rows = $this->baseQuery($dto, 'severity')
            ->whereNotNull('severity')
            ->select('severity', DB::raw('count(*) as count'))
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();

        $total = array_sum($rows);

        $data = [];
        foreach ($rows as $severity => $count) {
            $data[] = [
                'code'       => $severity,
                'label'      => $severity,
                'count'      => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 2) : 0.0,
            ];
        }

        return ['total' => $total, 'data' => $data];
    }

    /**
     * Work type distribution (cross-filtered: excludes work_type filter).
     */
    public function getWorkTypeChart(FilterProjectNotificationChartsDTO $dto): array
    {
        $rows = $this->baseQuery($dto, 'work_type')
            ->whereNotNull('work_type')
            ->select('work_type', DB::raw('count(*) as count'))
            ->groupBy('work_type')
            ->pluck('count', 'work_type')
            ->toArray();

        $total = array_sum($rows);

        $data = [];
        foreach ($rows as $workType => $count) {
            $data[] = [
                'code'       => $workType,
                'label'      => $workType,
                'count'      => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 2) : 0.0,
            ];
        }

        return ['total' => $total, 'data' => $data];
    }

    /**
     * Contractor category distribution (cross-filtered: excludes contractor_category filter).
     */
    public function getContractorCategoryChart(FilterProjectNotificationChartsDTO $dto): array
    {
        $rows = $this->baseQuery($dto, 'contractor_category')
            ->whereNotNull('contractor_category')
            ->select('contractor_category', DB::raw('count(*) as count'))
            ->groupBy('contractor_category')
            ->pluck('count', 'contractor_category')
            ->toArray();

        $total = array_sum($rows);

        $data = [];
        foreach ($rows as $category => $count) {
            $data[] = [
                'code'       => $category,
                'label'      => $category,
                'count'      => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 2) : 0.0,
            ];
        }

        return ['total' => $total, 'data' => $data];
    }

    /**
     * Project distribution (cross-filtered: excludes project_id filter).
     */
    public function getProjectChart(FilterProjectNotificationChartsDTO $dto): array
    {
        $rows = $this->baseQuery($dto, 'project_id')
            ->join('projects', 'projects.id', '=', 'project_notifications.project_id')
            ->select('projects.id as project_id', 'projects.name as project_name', DB::raw('count(*) as count'))
            ->groupBy('projects.id', 'projects.name')
            ->get();

        $total = $rows->sum('count');

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'code'       => $row->project_id,
                'label'      => $row->project_name,
                'count'      => (int) $row->count,
                'percentage' => $total > 0 ? round(($row->count / $total) * 100, 2) : 0.0,
            ];
        }

        return ['total' => (int) $total, 'data' => $data];
    }

    /**
     * Assigned employee distribution — each employee with their count of
     * assigned project notifications (cross-filtered: excludes assigned_user_id filter).
     */
    public function getAssignedEmployeeChart(FilterProjectNotificationChartsDTO $dto): array
    {
        $rows = $this->baseQuery($dto, 'assigned_user_id')
            ->whereNotNull('assigned_user_ids')
            ->leftJoin('users', function ($join) {
                $join->on('users.id', '=', DB::raw("JSON_UNQUOTE(JSON_EXTRACT(project_notifications.assigned_user_ids, '$[0]'))"));
            })
            ->select('users.id as user_id', 'users.name as user_name', DB::raw('count(*) as count'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('count')
            ->get();

        $total = $rows->sum('count');

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'code'       => $row->user_id,
                'label'      => $row->user_name ?? __('غير محدد'),
                'count'      => (int) $row->count,
                'percentage' => $total > 0 ? round(($row->count / $total) * 100, 2) : 0.0,
            ];
        }

        return ['total' => (int) $total, 'data' => $data];
    }

    /**
     * Contractor distribution — each contractor with their count of assigned
     * project notifications (cross-filtered: excludes contractor_id filter).
     */
    public function getContractorChart(FilterProjectNotificationChartsDTO $dto): array
    {
        $rows = $this->baseQuery($dto, 'contractor_id')
            ->whereNotNull('contractor_id')
            ->leftJoin('contractors', 'contractors.id', '=', 'project_notifications.contractor_id')
            ->select('contractors.id as contractor_id', 'contractors.name as contractor_name', DB::raw('count(*) as count'))
            ->groupBy('contractors.id', 'contractors.name')
            ->orderByDesc('count')
            ->get();

        $total = $rows->sum('count');

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'code'       => $row->contractor_id,
                'label'      => $row->contractor_name ?? __('غير محدد'),
                'count'      => (int) $row->count,
                'percentage' => $total > 0 ? round(($row->count / $total) * 100, 2) : 0.0,
            ];
        }

        return ['total' => (int) $total, 'data' => $data];
    }

    /**
     * Monthly trend (notifications created per month). Not cross-filtered by a
     * single dimension — uses all active filters.
     */
    public function getTrendChart(FilterProjectNotificationChartsDTO $dto): array
    {
        $rows = $this->baseQuery($dto)
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('count(*) as count'),
            )
            ->whereNotNull('created_at')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $total = $rows->sum('count');

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'month' => $row->month,
                'count' => (int) $row->count,
            ];
        }

        return ['total' => (int) $total, 'data' => $data];
    }

    /**
     * Resolve the pseudo-status code from raw status + location_confirmed_at.
     */
    private function resolveStatusCode(string $status, bool $locationConfirmed, bool $received = true): string
    {
        if ($status === 'in_progress') {
            return $locationConfirmed ? 'confirmed_location' : 'received';
        }

        if ($status === 'cancelled') {
            if (! $received) {
                return 'pending';
            }
            return $locationConfirmed ? 'confirmed_location' : 'received';
        }

        return $status;
    }

    /**
     * Resolve a human-readable label for a status value.
     * Uses the same labels as ProjectNotificationPresenter::statusLabel().
     */
    private function statusLabel(string $status): string
    {
        $locale = app()->getLocale();

        $labels = [
            'pending'            => ['ar' => 'بانتظار الرد', 'en' => 'Pending'],
            'received'           => ['ar' => 'تم الاستلام', 'en' => 'Received'],
            'confirmed_location' => ['ar' => 'تم تأكيد الموقع', 'en' => 'Confirmed Location'],
            'in_progress'        => ['ar' => 'تم تأكيد الموقع', 'en' => 'Confirmed Location'],
            'completed'          => ['ar' => 'مكتمل', 'en' => 'Completed'],
        ];

        return $labels[$status][$locale] ?? $status;
    }

    /**
     * Pick the locale-appropriate status name, falling back to Arabic then English,
     * and finally to a default label if no name is available.
     */
    private function resolveStatusName(?string $nameAr, ?string $nameEn, string $default): string
    {
        $locale = app()->getLocale();

        if ($locale === 'en' && $nameEn) {
            return $nameEn;
        }

        if ($nameAr) {
            return $nameAr;
        }

        if ($nameEn) {
            return $nameEn;
        }

        return $default;
    }
}
