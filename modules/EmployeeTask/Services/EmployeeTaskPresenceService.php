<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Models\EmployeeTaskSession;
use Modules\Project\ProjectManagement\Models\ProjectNotification;

/**
 * Resolves whether employees have a task that makes them "present" (متواجد)
 * on a given date, so attendance-style APIs can avoid reporting them as absent.
 *
 * Presence is derived from actual work sessions (activity), not merely from the
 * task's scheduled `task_date`, so a task created earlier that stays open marks
 * the employee present on every day it had a session. Both the primary assignee
 * (`EmployeeTaskRequest.user_id`) and every employee listed in a project
 * notification's `assigned_user_ids` are considered — the latter are otherwise
 * invisible because project-notification tasks are stored with a null company_id
 * (outside the tenant scope) and expose their assignees only on the notification.
 */
class EmployeeTaskPresenceService
{
    /**
     * Statuses that mean the employee is committed to / working on a task.
     * A task in one of these states can make the employee "present" (متواجد)
     * for the days it actually had activity instead of "absent".
     *
     * @return list<string>
     */
    public static function presentStatuses(): array
    {
        return [
            EmployeeTaskStatus::Approved->value,
            EmployeeTaskStatus::InProgress->value,
            EmployeeTaskStatus::Paused->value,
            EmployeeTaskStatus::Completed->value,
        ];
    }

    /**
     * Return the set of user IDs (as strings) that have a "present" task with
     * activity (a session) within the given date range.
     *
     * @param  iterable<int, mixed>  $userIds
     * @return array<int, string>
     */
    public function userIdsWithTaskInRange(iterable $userIds, ?string $startDate, ?string $endDate): array
    {
        $ids = collect($userIds)
            ->filter(static fn ($id) => $id !== null && $id !== '')
            ->map(static fn ($id) => (string) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $start = ($startDate !== null && $startDate !== '') ? $startDate : null;
        $end   = ($endDate !== null && $endDate !== '') ? $endDate : $start;

        $taskIdsByUser = $this->taskIdsByUser($ids->all());
        if ($taskIdsByUser === []) {
            return [];
        }

        $allTaskIds = collect($taskIdsByUser)->flatten()->unique()->values()->all();
        $activeDatesByTask = $this->activeDatesByTask($allTaskIds, $start, $end);

        $result = [];
        foreach ($taskIdsByUser as $userId => $taskIds) {
            foreach ($taskIds as $taskId) {
                if (! empty($activeDatesByTask[$taskId])) {
                    $result[] = (string) $userId;
                    break;
                }
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * Return the distinct Y-m-d dates on which a single user was actively working
     * (had a task session) within the given range.
     *
     * @return array<int, string>
     */
    public function taskDatesForUser(mixed $userId, string $startDate, string $endDate): array
    {
        if ($userId === null || $userId === '') {
            return [];
        }

        $taskIdsByUser = $this->taskIdsByUser([(string) $userId]);
        $taskIds = $taskIdsByUser[(string) $userId] ?? [];

        if ($taskIds === []) {
            return [];
        }

        $activeDatesByTask = $this->activeDatesByTask($taskIds, $startDate, $endDate);

        $dates = [];
        foreach ($taskIds as $taskId) {
            foreach ($activeDatesByTask[$taskId] ?? [] as $date) {
                $dates[$date] = true;
            }
        }

        $dates = array_keys($dates);
        sort($dates);

        return $dates;
    }

    /**
     * Convenience wrapper returning a lookup keyed by user ID.
     *
     * @param  iterable<int, mixed>  $userIds
     * @return Collection<string, bool>
     */
    public function presenceLookup(iterable $userIds, ?string $startDate, ?string $endDate): Collection
    {
        return collect($this->userIdsWithTaskInRange($userIds, $startDate, $endDate))
            ->mapWithKeys(static fn (string $id): array => [$id => true]);
    }

    /**
     * For the given user IDs and date range, return per-user, per-day task
     * presence derived from actual sessions, including the worked minutes and
     * the task titles active on each day. Used by attendance reports so
     * on-task days are counted as attended (متواجد) with their real hours.
     *
     * @param  iterable<int, mixed>  $userIds
     * @return array<string, array<string, array{minutes: int, titles: list<string>}>>
     *         map: userId => (Y-m-d => ['minutes' => int, 'titles' => list<string>])
     */
    public function taskPresenceDetailsForUsers(iterable $userIds, string $startDate, string $endDate): array
    {
        $ids = collect($userIds)
            ->filter(static fn ($id) => $id !== null && $id !== '')
            ->map(static fn ($id) => (string) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $taskIdsByUser = $this->taskIdsByUser($ids->all());
        if ($taskIdsByUser === []) {
            return [];
        }

        $allTaskIds     = collect($taskIdsByUser)->flatten()->unique()->values()->all();
        $minutesByTask  = $this->activeMinutesByTask($allTaskIds, $startDate, $endDate);
        $titlesByTask   = $this->taskTitles($allTaskIds);

        $result = [];
        foreach ($taskIdsByUser as $userId => $taskIds) {
            $perDay = [];
            foreach ($taskIds as $taskId) {
                $title = $titlesByTask[$taskId] ?? null;
                foreach ($minutesByTask[$taskId] ?? [] as $date => $minutes) {
                    if (! isset($perDay[$date])) {
                        $perDay[$date] = ['minutes' => 0, 'titles' => []];
                    }
                    $perDay[$date]['minutes'] += $minutes;
                    if ($title !== null && $title !== '' && ! in_array($title, $perDay[$date]['titles'], true)) {
                        $perDay[$date]['titles'][] = $title;
                    }
                }
            }

            if ($perDay !== []) {
                ksort($perDay);
                $result[(string) $userId] = $perDay;
            }
        }

        return $result;
    }

    /**
     * TEMPORARY diagnostics for the calendar presence pipeline for a single user.
     * Returns every intermediate step so we can see why on_task days do/don't show.
     *
     * @return array<string, mixed>
     */
    public function debugForUser(mixed $userId, string $startDate, string $endDate): array
    {
        $uid = (string) $userId;

        $directTasks = EmployeeTaskRequest::query()
            ->where('user_id', $uid)
            ->get(['id', 'user_id', 'status', 'task_date', 'company_id', 'is_project_notification', 'time_from', 'time_to'])
            ->map(static fn ($t) => [
                'id'                      => (string) $t->id,
                'status'                  => $t->status,
                'task_date'               => (string) $t->task_date,
                'company_id'              => $t->company_id,
                'is_project_notification' => (bool) $t->is_project_notification,
                'time_from'               => (string) $t->time_from,
                'time_to'                 => (string) $t->time_to,
            ])->values()->all();

        $directTasksPresent = EmployeeTaskRequest::query()
            ->where('user_id', $uid)
            ->whereIn('status', self::presentStatuses())
            ->pluck('id')
            ->map(static fn ($id) => (string) $id)
            ->all();

        $notifications = ProjectNotification::query()
            ->whereJsonContains('assigned_user_ids', $uid)
            ->get(['id', 'employee_task_request_id', 'assigned_user_ids', 'status', 'task_date'])
            ->map(static fn ($n) => [
                'id'                       => (string) $n->id,
                'employee_task_request_id' => (string) $n->employee_task_request_id,
                'assigned_user_ids'        => $n->assigned_user_ids,
                'notification_status'      => $n->status,
                'task_date'                => (string) $n->task_date,
            ])->values()->all();

        $taskIdsByUser = $this->taskIdsByUser([$uid]);
        $resolvedTaskIds = $taskIdsByUser[$uid] ?? [];

        $sessions = EmployeeTaskSession::query()
            ->whereIn('employee_task_request_id', $resolvedTaskIds === [] ? ['__none__'] : $resolvedTaskIds)
            ->get(['employee_task_request_id', 'start_time', 'end_time'])
            ->map(static fn ($s) => [
                'task_id'    => (string) $s->employee_task_request_id,
                'start_time' => $s->start_time?->format('Y-m-d H:i:s'),
                'end_time'   => $s->end_time?->format('Y-m-d H:i:s'),
            ])->values()->all();

        return [
            'user_id'                 => $uid,
            'range'                   => ['start' => $startDate, 'end' => $endDate],
            'timezone'                => $this->timezone(),
            'now'                     => CarbonImmutable::now($this->timezone())->format('Y-m-d H:i:s'),
            'direct_tasks_all'        => $directTasks,
            'direct_tasks_present'    => $directTasksPresent,
            'notifications_assigned'  => $notifications,
            'resolved_task_ids'       => $resolvedTaskIds,
            'sessions_for_tasks'      => $sessions,
            'active_dates_by_task'    => $this->activeDatesByTask($resolvedTaskIds, $startDate, $endDate),
            'task_dates_for_user'     => $this->taskDatesForUser($uid, $startDate, $endDate),
        ];
    }

    /**
     * Resolve, for each of the given users, the IDs of tasks that could make them
     * present. Covers two assignment paths:
     *   1. Regular tasks assigned directly via EmployeeTaskRequest.user_id
     *      (tenant-scoped: these carry a company_id).
     *   2. Project-notification tasks, whose assignees live on the notification's
     *      assigned_user_ids JSON array. These tasks have a null company_id and are
     *      therefore invisible to a tenant-scoped EmployeeTaskRequest query, so we
     *      reach them through the (tenant-scoped) ProjectNotification instead.
     *
     * @param  list<string>  $userIds
     * @return array<string, list<string>>  map of userId => list<taskId>
     */
    private function taskIdsByUser(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $map = [];

        EmployeeTaskRequest::query()
            ->whereIn('user_id', $userIds)
            ->whereIn('status', self::presentStatuses())
            ->get(['id', 'user_id'])
            ->each(static function (EmployeeTaskRequest $task) use (&$map): void {
                $map[(string) $task->user_id][] = (string) $task->id;
            });

        $notifications = ProjectNotification::query()
            ->whereNotNull('employee_task_request_id')
            ->whereHas('employeeTask', static fn ($query) => $query->whereIn('status', self::presentStatuses()))
            ->where(function ($query) use ($userIds): void {
                foreach ($userIds as $id) {
                    $query->orWhereJsonContains('assigned_user_ids', (string) $id);
                }
            })
            ->get(['id', 'employee_task_request_id', 'assigned_user_ids']);

        $requested = array_map('strval', $userIds);

        foreach ($notifications as $notification) {
            $taskId   = (string) $notification->employee_task_request_id;
            $assigned = array_map('strval', $notification->assigned_user_ids ?? []);

            foreach (array_intersect($requested, $assigned) as $id) {
                $map[$id][] = $taskId;
            }
        }

        foreach ($map as $id => $taskIds) {
            $map[$id] = array_values(array_unique($taskIds));
        }

        return $map;
    }

    /**
     * For the given task IDs, return the Y-m-d dates on which each task had an
     * active work session overlapping [startDate, endDate]. An open session
     * (no end_time) is treated as active through today.
     *
     * @param  list<string>  $taskIds
     * @return array<string, list<string>>  map of taskId => list<Y-m-d date>
     */
    private function activeDatesByTask(array $taskIds, ?string $startDate, ?string $endDate): array
    {
        $out = [];
        foreach ($this->activeMinutesByTask($taskIds, $startDate, $endDate) as $taskId => $days) {
            $out[$taskId] = array_keys($days);
        }

        return $out;
    }

    /**
     * For the given task IDs, return the worked minutes per Y-m-d date, derived
     * from the portion of each work session that falls within that day (clamped
     * to [startDate, endDate]). An open session (no end_time) is treated as
     * active through "now". Days that are only partially overlapped still appear
     * (possibly with 0 minutes) so callers can treat them as present.
     *
     * @param  list<string>  $taskIds
     * @return array<string, array<string, int>>  map of taskId => (Y-m-d => minutes)
     */
    private function activeMinutesByTask(array $taskIds, ?string $startDate, ?string $endDate): array
    {
        if ($taskIds === []) {
            return [];
        }

        $rangeStartStr = $startDate !== null ? $startDate . ' 00:00:00' : null;
        $rangeEndStr   = $endDate !== null ? $endDate . ' 23:59:59' : null;

        $sessions = EmployeeTaskSession::query()
            ->whereIn('employee_task_request_id', $taskIds)
            ->whereNotNull('start_time')
            ->when(
                $rangeEndStr !== null,
                static fn ($query) => $query->where('start_time', '<=', $rangeEndStr)
            )
            ->when(
                $rangeStartStr !== null,
                static fn ($query) => $query->where(static function ($q) use ($rangeStartStr): void {
                    $q->whereNull('end_time')->orWhere('end_time', '>=', $rangeStartStr);
                })
            )
            ->get(['employee_task_request_id', 'start_time', 'end_time']);

        $rangeStart = $rangeStartStr !== null ? CarbonImmutable::parse($rangeStartStr) : null;
        $rangeEnd   = $rangeEndStr !== null ? CarbonImmutable::parse($rangeEndStr) : null;
        $now        = CarbonImmutable::parse(CarbonImmutable::now($this->timezone())->format('Y-m-d H:i:s'));

        $map = [];
        foreach ($sessions as $session) {
            $taskId = (string) $session->employee_task_request_id;

            if ($session->start_time === null) {
                continue;
            }

            $start = CarbonImmutable::parse($session->start_time->format('Y-m-d H:i:s'));
            $end   = $session->end_time !== null
                ? CarbonImmutable::parse($session->end_time->format('Y-m-d H:i:s'))
                : $now;

            if ($end->lt($start)) {
                $end = $start;
            }
            if ($rangeStart !== null && $start->lt($rangeStart)) {
                $start = $rangeStart;
            }
            if ($rangeEnd !== null && $end->gt($rangeEnd)) {
                $end = $rangeEnd;
            }
            if ($end->lt($start)) {
                continue;
            }

            $cursorDay = $start->startOfDay();
            $lastDay   = $end->startOfDay();
            while ($cursorDay->lte($lastDay)) {
                $segStart = $start->gt($cursorDay) ? $start : $cursorDay;
                $dayEnd   = $cursorDay->endOfDay();
                $segEnd   = $end->lt($dayEnd) ? $end : $dayEnd;
                $minutes  = $segEnd->gt($segStart) ? (int) round($segStart->diffInMinutes($segEnd)) : 0;

                $key = $cursorDay->format('Y-m-d');
                $map[$taskId][$key] = ($map[$taskId][$key] ?? 0) + $minutes;

                $cursorDay = $cursorDay->addDay();
            }
        }

        // Fallback: currently active tasks (in_progress / paused) may have no
        // session rows at all (e.g. multi-assignee project notifications where the
        // task was started once and secondary assignees never produced a session).
        // Treat such a task as making its assignees present from its start date
        // through today, so the employee isn't wrongly shown as absent.
        $this->mergeActiveTaskSpans($map, $taskIds, $startDate, $endDate);

        return $map;
    }

    /**
     * Merge presence day-spans for tasks that are currently active
     * (in_progress / paused) but may lack session rows. Days already present in
     * $map keep their session minutes; new days are added with 0 minutes so they
     * still count as present without inflating worked hours.
     *
     * @param  array<string, array<string, int>>  $map  taskId => (Y-m-d => minutes)
     * @param  list<string>  $taskIds
     */
    private function mergeActiveTaskSpans(array &$map, array $taskIds, ?string $startDate, ?string $endDate): void
    {
        $activeTasks = EmployeeTaskRequest::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $taskIds)
            ->whereIn('status', [
                EmployeeTaskStatus::InProgress->value,
                EmployeeTaskStatus::Paused->value,
            ])
            ->get(['id', 'time_from', 'task_date']);

        if ($activeTasks->isEmpty()) {
            return;
        }

        $notificationDates = ProjectNotification::query()
            ->whereIn('employee_task_request_id', $activeTasks->pluck('id')->map(static fn ($id) => (string) $id)->all())
            ->get(['employee_task_request_id', 'task_date'])
            ->mapWithKeys(static fn ($n) => [
                (string) $n->employee_task_request_id => $n->task_date?->format('Y-m-d'),
            ]);

        $todayStr = CarbonImmutable::now($this->timezone())->format('Y-m-d');

        foreach ($activeTasks as $task) {
            $taskId = (string) $task->id;

            $startDay = $task->time_from?->format('Y-m-d')
                ?? $task->task_date?->format('Y-m-d')
                ?? ($notificationDates[$taskId] ?? null)
                ?? $todayStr;

            $endDay = $todayStr;

            if ($startDate !== null && $startDay < $startDate) {
                $startDay = $startDate;
            }
            if ($endDate !== null && $endDay > $endDate) {
                $endDay = $endDate;
            }
            if ($startDate !== null && $endDay < $startDate) {
                continue;
            }
            if ($endDate !== null && $startDay > $endDate) {
                continue;
            }
            if ($startDay > $endDay) {
                continue;
            }

            $cursor = CarbonImmutable::parse($startDay);
            $last   = CarbonImmutable::parse($endDay);
            while ($cursor->lte($last)) {
                $key = $cursor->format('Y-m-d');
                if (! isset($map[$taskId][$key])) {
                    $map[$taskId][$key] = 0;
                }
                $cursor = $cursor->addDay();
            }
        }
    }

    /**
     * Resolve a display title for each task ID. Falls back to the linked project
     * notification's description/number when the task itself has no title. Uses
     * withoutGlobalScopes because project-notification tasks carry a null
     * company_id and would otherwise be filtered out by the tenant scope.
     *
     * @param  list<string>  $taskIds
     * @return array<string, string>  map of taskId => title
     */
    private function taskTitles(array $taskIds): array
    {
        if ($taskIds === []) {
            return [];
        }

        $titles = [];
        EmployeeTaskRequest::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $taskIds)
            ->get(['id', 'title'])
            ->each(static function (EmployeeTaskRequest $task) use (&$titles): void {
                if ($task->title !== null && $task->title !== '') {
                    $titles[(string) $task->id] = (string) $task->title;
                }
            });

        $missing = array_values(array_filter(
            $taskIds,
            static fn (string $id): bool => ! isset($titles[$id])
        ));

        if ($missing !== []) {
            ProjectNotification::query()
                ->whereIn('employee_task_request_id', $missing)
                ->get(['employee_task_request_id', 'work_description', 'notification_number'])
                ->each(static function (ProjectNotification $notification) use (&$titles): void {
                    $taskId = (string) $notification->employee_task_request_id;
                    $title  = $notification->work_description !== null && $notification->work_description !== ''
                        ? (string) $notification->work_description
                        : trim('إشعار ' . (string) $notification->notification_number);
                    $titles[$taskId] = $title;
                });
        }

        return $titles;
    }

    private function timezone(): string
    {
        if (function_exists('getTimeZoneBranchByRequest')) {
            $tz = getTimeZoneBranchByRequest();
            if (is_string($tz) && $tz !== '') {
                return $tz;
            }
        }

        return (string) (config('app.timezone') ?? 'UTC');
    }
}
