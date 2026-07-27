<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Modules\Attendance\Contracts\TemporaryLocationProvider;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\User\Models\User;

/**
 * Exposes the geofence of an employee's active (in_progress) task to the
 * Attendance module as a TEMPORARY additional clock-in location
 * (Attendance Rules V2, Feature 6, §10.2).
 *
 * Attendance resolves the implementations through the
 * 'attendance.temporary_location_providers' container tag, so it never
 * imports EmployeeTask classes (EmployeeTask already depends on Attendance).
 */
final class EmployeeTaskTemporaryLocationProvider implements TemporaryLocationProvider
{
    private const DEFAULT_RADIUS_METRES = 100;

    public function temporaryLocationsFor(User $user, CarbonImmutable $at): array
    {
        return $this->activeLocatedTasksQuery($user)
            ->get()
            // Do not trust status alone: a stuck in_progress row past its
            // duration (lost auto-close job) must NOT emit a location.
            ->filter(fn (EmployeeTaskRequest $task) => $at->lessThanOrEqualTo($this->taskExpiresAt($task)))
            ->map(fn (EmployeeTaskRequest $task) => [
                'id'           => 'task:'.$task->id,
                'name'         => $this->displayName($task),
                'latitude'     => (float) $task->task_latitude,
                'longitude'    => (float) $task->task_longitude,
                'radius'       => (int) ($task->radius_meters ?? self::DEFAULT_RADIUS_METRES),
                'source'       => 'employee_task',
                'expires_at'   => $this->taskExpiresAt($task)->toIso8601String(),
                'reference_id' => (string) $task->id,
            ])
            ->values()
            ->all();
    }

    public function isEngagedElsewhere(User $user, CarbonImmutable $at): bool
    {
        foreach ($this->activeLocatedTasksQuery($user)->cursor() as $task) {
            if ($at->lessThanOrEqualTo($this->taskExpiresAt($task))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Company scoping is applied by the model's CustomTenantScope global scope
     * (CustomBelongsToTenant) whenever tenancy is initialized — the same
     * tenancy pattern the other EmployeeTask services rely on.
     */
    private function activeLocatedTasksQuery(User $user): Builder
    {
        return EmployeeTaskRequest::query()
            ->where('user_id', $user->id)
            ->where('status', EmployeeTaskStatus::InProgress->value)
            ->whereNotNull('time_from')
            ->whereNotNull('task_latitude')
            ->whereNotNull('task_longitude');
    }

    /**
     * time_from + duration_hours, in the task's frozen timezone.
     *
     * Module timezone invariant: time_from is stored as a branch-TZ wall-clock
     * string, so the RAW string is parsed with the timezone as the SECOND
     * argument. Passing the Eloquent datetime cast here would silently ignore
     * the timezone (Carbon 3 keeps the instance's own tz for DateTimeInterface
     * input), and parse-then-setTimezone() would re-interpret and shift it.
     */
    private function taskExpiresAt(EmployeeTaskRequest $task): CarbonImmutable
    {
        return CarbonImmutable::parse(
            $task->getRawOriginal('time_from'),
            $task->timezone ?: config('app.timezone'),
        )->addMinutes((int) round(((float) $task->duration_hours) * 60));
    }

    private function displayName(EmployeeTaskRequest $task): string
    {
        if (is_string($task->title) && trim($task->title) !== '') {
            return $task->title;
        }

        return $task->is_project_notification ? 'Project notification task' : 'Employee task';
    }
}
