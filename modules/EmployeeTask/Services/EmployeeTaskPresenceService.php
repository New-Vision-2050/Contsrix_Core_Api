<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Illuminate\Support\Collection;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;

/**
 * Resolves whether employees have a task that makes them "present" (متواجد)
 * on a given date, so attendance-style APIs can avoid reporting them as absent.
 */
class EmployeeTaskPresenceService
{
    /**
     * Statuses that mean the employee is committed to / working on a task.
     * A task in one of these states makes the employee "present" (متواجد)
     * for its scheduled date instead of "absent".
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
     * Return the set of user IDs (as strings) that have a "present" task
     * scheduled within the given date range.
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

        return EmployeeTaskRequest::query()
            ->whereIn('user_id', $ids->all())
            ->whereIn('status', self::presentStatuses())
            ->when(
                $startDate !== null && $startDate !== '',
                static fn ($query) => $query->whereDate('task_date', '>=', $startDate)
            )
            ->when(
                $endDate !== null && $endDate !== '',
                static fn ($query) => $query->whereDate('task_date', '<=', $endDate)
            )
            ->distinct()
            ->pluck('user_id')
            ->map(static fn ($id) => (string) $id)
            ->all();
    }

    /**
     * Return the distinct Y-m-d dates on which a single user has a "present"
     * task within the given range.
     *
     * @return array<int, string>
     */
    public function taskDatesForUser(mixed $userId, string $startDate, string $endDate): array
    {
        if ($userId === null || $userId === '') {
            return [];
        }

        return EmployeeTaskRequest::query()
            ->where('user_id', (string) $userId)
            ->whereIn('status', self::presentStatuses())
            ->whereDate('task_date', '>=', $startDate)
            ->whereDate('task_date', '<=', $endDate)
            ->pluck('task_date')
            ->map(static function ($date): ?string {
                if ($date === null) {
                    return null;
                }

                return $date instanceof \DateTimeInterface
                    ? $date->format('Y-m-d')
                    : substr((string) $date, 0, 10);
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
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
}
