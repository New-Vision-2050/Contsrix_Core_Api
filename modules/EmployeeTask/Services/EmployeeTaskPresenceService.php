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
     * A session left open (no end_time) is credited for at most this many hours
     * after its start, which covers overnight work while preventing a forgotten
     * session from marking every later day as worked.
     */
    private const MAX_OPEN_SESSION_HOURS = 24;

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
     * Return, for a single user, the tasks that made them present (متواجد) on
     * each day of the range, keyed by Y-m-d date. Each entry carries enough
     * metadata for the UI to show which task caused the status.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function taskDetailsForUser(mixed $userId, string $startDate, string $endDate): array
    {
        if ($userId === null || $userId === '') {
            return [];
        }

        $taskIds = $this->taskIdsByUser([(string) $userId])[(string) $userId] ?? [];

        if ($taskIds === []) {
            return [];
        }

        $minutesByTask = $this->activeMinutesByTask($taskIds, $startDate, $endDate);

        if ($minutesByTask === []) {
            return [];
        }

        $metadata = $this->taskMetadata($taskIds);

        $byDate = [];
        foreach ($taskIds as $taskId) {
            $taskId = (string) $taskId;

            foreach ($minutesByTask[$taskId] ?? [] as $date => $minutes) {
                $minutes = (int) $minutes;

                $byDate[$date][] = array_merge(
                    $metadata[$taskId] ?? ['id' => $taskId],
                    [
                        'minutes' => $minutes,
                        'hours'   => round($minutes / 60, 2),
                    ]
                );
            }
        }

        ksort($byDate);

        return $byDate;
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
     * to [startDate, endDate]). Days that are only partially overlapped still
     * appear (possibly with 0 minutes) so callers can treat them as present.
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

        $tasks = EmployeeTaskRequest::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $taskIds)
            ->get(['id', 'status', 'time_from', 'time_to', 'task_date'])
            ->keyBy(static fn ($task) => (string) $task->id);

        $map = [];
        foreach ($sessions as $session) {
            $taskId = (string) $session->employee_task_request_id;

            if ($session->start_time === null) {
                continue;
            }

            $taskEnd = $tasks->get($taskId)?->time_to;

            $start = CarbonImmutable::parse($session->start_time->format('Y-m-d H:i:s'));
            $end   = $this->resolveSessionEnd(
                $start,
                $session->end_time !== null
                    ? CarbonImmutable::parse($session->end_time->format('Y-m-d H:i:s'))
                    : null,
                $taskEnd !== null
                    ? CarbonImmutable::parse($taskEnd->format('Y-m-d H:i:s'))
                    : null,
                $now
            );

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
        // Treat such a task as making its assignees present on the days the task
        // itself belongs to, so the employee isn't wrongly shown as absent.
        $this->mergeActiveTaskDays($map, $taskIds, $tasks, $startDate, $endDate);

        return $map;
    }

    /**
     * Resolve the effective end of a work session. A closed session ends at its
     * own end_time; an open one is only credited up to MAX_OPEN_SESSION_HOURS
     * after its start (or the task's own end, when that comes first), because a
     * session left open by mistake would otherwise mark the employee present on
     * every single day since it started.
     */
    private function resolveSessionEnd(
        CarbonImmutable $start,
        ?CarbonImmutable $end,
        ?CarbonImmutable $taskEnd,
        CarbonImmutable $now
    ): CarbonImmutable {
        if ($end !== null) {
            return $end->lt($start) ? $start : $end;
        }

        $cap = $start->addHours(self::MAX_OPEN_SESSION_HOURS);

        if ($taskEnd !== null && $taskEnd->gt($start) && $taskEnd->lt($cap)) {
            $cap = $taskEnd;
        }

        return $now->lt($cap) ? $now : $cap;
    }

    /**
     * Merge presence days for tasks that are active but may lack session rows.
     * This covers two cases that otherwise show "absent":
     *   - regular tasks in in_progress / paused status;
     *   - project-notification tasks whose linked notification is in_progress
     *     (received) or completed — the task row itself can lag at "approved" and
     *     never produce a session, so we drive the days from the notification.
     * Days already present in $map keep their session minutes; new days are added
     * with 0 minutes so they still count as present without inflating hours.
     *
     * @param  array<string, array<string, int>>  $map  taskId => (Y-m-d => minutes)
     * @param  list<string>  $taskIds
     * @param  \Illuminate\Support\Collection<string, EmployeeTaskRequest>  $tasks
     */
    private function mergeActiveTaskDays(
        array &$map,
        array $taskIds,
        Collection $tasks,
        ?string $startDate,
        ?string $endDate
    ): void {
        if ($taskIds === []) {
            return;
        }

        $todayStr = CarbonImmutable::now($this->timezone())->format('Y-m-d');

        $notifications = ProjectNotification::query()
            ->whereIn('employee_task_request_id', $taskIds)
            ->get(['employee_task_request_id', 'status', 'task_date'])
            ->keyBy(static fn ($n) => (string) $n->employee_task_request_id);

        foreach ($taskIds as $taskId) {
            $taskId = (string) $taskId;

            $days = $this->resolveActiveDays(
                $tasks->get($taskId),
                $notifications->get($taskId),
                $todayStr,
            );

            foreach ($days as $day) {
                if ($startDate !== null && $day < $startDate) {
                    continue;
                }
                if ($endDate !== null && $day > $endDate) {
                    continue;
                }
                if (! isset($map[$taskId][$day])) {
                    $map[$taskId][$day] = 0;
                }
            }
        }
    }

    /**
     * Resolve the days an active task makes its assignees present, even without
     * session rows: the day the task belongs to, the day it ended, and today
     * while it is still running. Days in between are only counted when they have
     * a real work session, so a task left open for weeks does not mark every
     * day in between as "متواجد". Returns [] when the task is not active.
     *
     * @return list<string>  Y-m-d days
     */
    private function resolveActiveDays(?EmployeeTaskRequest $task, ?ProjectNotification $notification, string $todayStr): array
    {
        $taskDay = $task?->task_date?->format('Y-m-d')
            ?? $task?->time_from?->format('Y-m-d');
        $endDay  = $task?->time_to?->format('Y-m-d');

        if ($notification !== null) {
            $startDay = $notification->task_date?->format('Y-m-d') ?? $taskDay;

            if ($notification->status === 'in_progress') {
                return $this->uniqueDays([$startDay, $taskDay, $endDay, $todayStr]);
            }

            if ($notification->status === 'completed') {
                return $this->uniqueDays([$startDay, $taskDay, $endDay]);
            }

            return [];
        }

        if ($task !== null && in_array($task->status, [
            EmployeeTaskStatus::InProgress->value,
            EmployeeTaskStatus::Paused->value,
        ], true)) {
            return $this->uniqueDays([$taskDay, $endDay, $todayStr]);
        }

        return [];
    }

    /**
     * @param  list<?string>  $days
     * @return list<string>
     */
    private function uniqueDays(array $days): array
    {
        return array_values(array_unique(array_filter(
            $days,
            static fn (?string $day): bool => $day !== null && $day !== ''
        )));
    }

    /**
     * Resolve a display title for each task ID.
     *
     * @param  list<string>  $taskIds
     * @return array<string, string>  map of taskId => title
     */
    private function taskTitles(array $taskIds): array
    {
        $titles = [];

        foreach ($this->taskMetadata($taskIds) as $taskId => $meta) {
            $title = $meta['title'] ?? null;
            if (is_string($title) && $title !== '') {
                $titles[$taskId] = $title;
            }
        }

        return $titles;
    }

    /**
     * Resolve display metadata for each task ID. The title falls back to the
     * linked project notification's description/number when the task itself has
     * no title. Uses withoutGlobalScopes because project-notification tasks
     * carry a null company_id and would otherwise be filtered out by the tenant
     * scope.
     *
     * @param  list<string>  $taskIds
     * @return array<string, array<string, mixed>>  map of taskId => metadata
     */
    private function taskMetadata(array $taskIds): array
    {
        if ($taskIds === []) {
            return [];
        }

        $meta = [];

        EmployeeTaskRequest::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $taskIds)
            ->get(['id', 'title', 'status', 'task_date', 'project_id', 'is_project_notification'])
            ->each(static function (EmployeeTaskRequest $task) use (&$meta): void {
                $meta[(string) $task->id] = [
                    'id'                  => (string) $task->id,
                    'title'               => ($task->title !== null && $task->title !== '') ? (string) $task->title : null,
                    'status'              => $task->status !== null ? (string) $task->status : null,
                    'task_date'           => $task->task_date?->format('Y-m-d'),
                    'project_id'          => $task->project_id !== null ? (string) $task->project_id : null,
                    'source'              => $task->is_project_notification ? 'project_notification' : 'employee_task',
                    'notification_id'     => null,
                    'notification_number' => null,
                    'notification_status' => null,
                ];
            });

        ProjectNotification::query()
            ->whereIn('employee_task_request_id', $taskIds)
            ->get(['id', 'employee_task_request_id', 'project_id', 'work_description', 'notification_number', 'status'])
            ->each(static function (ProjectNotification $notification) use (&$meta): void {
                $taskId = (string) $notification->employee_task_request_id;

                $entry = $meta[$taskId] ?? [
                    'id'         => $taskId,
                    'title'      => null,
                    'status'     => null,
                    'task_date'  => null,
                    'project_id' => null,
                ];

                if (($entry['title'] ?? null) === null) {
                    $entry['title'] = ($notification->work_description !== null && $notification->work_description !== '')
                        ? (string) $notification->work_description
                        : trim('إشعار ' . (string) $notification->notification_number);
                }

                $entry['source']              = 'project_notification';
                $entry['notification_id']     = (string) $notification->id;
                $entry['notification_number'] = $notification->notification_number !== null
                    ? (string) $notification->notification_number
                    : null;
                $entry['notification_status'] = $notification->status !== null ? (string) $notification->status : null;
                $entry['project_id']          = $entry['project_id']
                    ?? ($notification->project_id !== null ? (string) $notification->project_id : null);

                $meta[$taskId] = $entry;
            });

        return $meta;
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
