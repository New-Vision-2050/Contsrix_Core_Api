<?php

declare(strict_types=1);

namespace Modules\Attendance\Support;

use Carbon\Carbon;
use Modules\User\Models\User;

/**
 * The granted attendance-status ranges for one employee.
 *
 * PATCH /sub_entities/records/attendance-status adds a range; it does not replace
 * earlier disjoint days. Adjacent/overlapping ranges of the same status merge.
 * Applying the opposite status punches a hole in the other (INV-18).
 *
 * @phpstan-type Range array{status: string, starts_on: ?string, ends_on: ?string}
 */
final class ManualAttendanceOverrideSet
{
    /**
     * @param  list<Range>  $ranges
     */
    private function __construct(private array $ranges)
    {
    }

    public static function none(): self
    {
        return new self([]);
    }

    public static function fromLegacy(?string $status, mixed $since, mixed $until): self
    {
        if (! in_array($status, [ManualAttendanceStatus::HOLIDAY, ManualAttendanceStatus::REQUIRED_ATTENDANCE], true)) {
            return self::none();
        }

        return new self([[
            'status' => $status,
            'starts_on' => self::toDateString($since),
            'ends_on' => self::toDateString($until),
        ]]);
    }

    /**
     * Prefer stored ranges when they exist; otherwise the three `users` columns
     * (unmigrated rows, and PHPUnit fixtures that never hit the table).
     */
    public static function fromUser(?User $user): self
    {
        if (! $user) {
            return self::none();
        }

        if (! $user->relationLoaded('manualAttendanceOverrides') && $user->exists && $user->getKey()) {
            $user->load('manualAttendanceOverrides');
        }

        if ($user->relationLoaded('manualAttendanceOverrides') && $user->manualAttendanceOverrides->isNotEmpty()) {
            return self::fromModels($user->manualAttendanceOverrides);
        }

        return self::fromLegacy(
            $user->manual_attendance_status ?? null,
            $user->manual_attendance_status_since ?? null,
            $user->manual_attendance_status_until ?? null
        );
    }

    /**
     * @param  iterable<int, object>  $models
     */
    public static function fromModels(iterable $models): self
    {
        $ranges = [];

        foreach ($models as $model) {
            $status = $model->status ?? null;
            if (! in_array($status, [ManualAttendanceStatus::HOLIDAY, ManualAttendanceStatus::REQUIRED_ATTENDANCE], true)) {
                continue;
            }

            $ranges[] = [
                'status' => $status,
                'starts_on' => self::toDateString($model->starts_on ?? null),
                'ends_on' => self::toDateString($model->ends_on ?? null),
            ];
        }

        return new self($ranges);
    }

    public function activeOn(string $date): ?string
    {
        $holiday = false;

        foreach ($this->ranges as $range) {
            if (! self::covers($range, $date)) {
                continue;
            }

            if ($range['status'] === ManualAttendanceStatus::REQUIRED_ATTENDANCE) {
                return ManualAttendanceStatus::REQUIRED_ATTENDANCE;
            }

            if ($range['status'] === ManualAttendanceStatus::HOLIDAY) {
                $holiday = true;
            }
        }

        return $holiday ? ManualAttendanceStatus::HOLIDAY : null;
    }

    public function isHolidayOn(string $date): bool
    {
        return $this->activeOn($date) === ManualAttendanceStatus::HOLIDAY;
    }

    public function isRequiredAttendanceOn(string $date): bool
    {
        return $this->activeOn($date) === ManualAttendanceStatus::REQUIRED_ATTENDANCE;
    }

    /**
     * The holiday range that covers `$date`, for list payload date_from / date_to.
     *
     * @return array{date_from: string, date_to: string|null}|null
     */
    public function holidayRangeCovering(string $date): ?array
    {
        if (! $this->isHolidayOn($date)) {
            return null;
        }

        foreach ($this->ranges as $range) {
            if ($range['status'] !== ManualAttendanceStatus::HOLIDAY || ! self::covers($range, $date)) {
                continue;
            }

            return [
                'date_from' => $range['starts_on'] ?? $date,
                'date_to' => $range['ends_on'],
            ];
        }

        return null;
    }

    /**
     * Add `$status` on `[from, to]` (null `to` is open-ended). Opposite-status
     * coverage on those dates is removed; same-status ranges that touch merge.
     */
    public function withApplied(string $status, string $from, ?string $to): self
    {
        if (! in_array($status, [ManualAttendanceStatus::HOLIDAY, ManualAttendanceStatus::REQUIRED_ATTENDANCE], true)) {
            return $this;
        }

        $opposite = $status === ManualAttendanceStatus::HOLIDAY
            ? ManualAttendanceStatus::REQUIRED_ATTENDANCE
            : ManualAttendanceStatus::HOLIDAY;

        $kept = [];
        foreach ($this->ranges as $range) {
            if ($range['status'] !== $opposite) {
                $kept[] = $range;
                continue;
            }

            foreach (self::subtractRange($range, $from, $to) as $piece) {
                $kept[] = $piece;
            }
        }

        $kept[] = [
            'status' => $status,
            'starts_on' => $from,
            'ends_on' => $to,
        ];

        return new self(self::mergeSameStatus($kept));
    }

    /**
     * @return list<Range>
     */
    public function ranges(): array
    {
        return $this->ranges;
    }

    /**
     * @param  Range  $range
     */
    private static function covers(array $range, string $date): bool
    {
        if ($range['starts_on'] !== null && $range['starts_on'] > $date) {
            return false;
        }

        if ($range['ends_on'] !== null && $date > $range['ends_on']) {
            return false;
        }

        return true;
    }

    /**
     * @param  Range  $range
     * @return list<Range>
     */
    private static function subtractRange(array $range, string $cutFrom, ?string $cutTo): array
    {
        if (! self::intersects($range, $cutFrom, $cutTo)) {
            return [$range];
        }

        $status = $range['status'];
        $start = $range['starts_on'];
        $end = $range['ends_on'];
        $pieces = [];

        $leftEnd = self::earlierDate($end, self::shift($cutFrom, -1));
        if ($leftEnd !== null && ($start === null || $start <= $leftEnd)) {
            $pieces[] = [
                'status' => $status,
                'starts_on' => $start,
                'ends_on' => $leftEnd,
            ];
        }

        if ($cutTo === null) {
            return $pieces;
        }

        $rightStart = self::shift($cutTo, 1);
        if ($end !== null && $end < $rightStart) {
            return $pieces;
        }

        $newStart = $start !== null && $start > $rightStart ? $start : $rightStart;
        if ($end === null || $newStart <= $end) {
            $pieces[] = [
                'status' => $status,
                'starts_on' => $newStart,
                'ends_on' => $end,
            ];
        }

        return $pieces;
    }

    /**
     * @param  Range  $range
     */
    private static function intersects(array $range, string $cutFrom, ?string $cutTo): bool
    {
        if ($range['ends_on'] !== null && $range['ends_on'] < $cutFrom) {
            return false;
        }

        if ($cutTo !== null && $range['starts_on'] !== null && $range['starts_on'] > $cutTo) {
            return false;
        }

        return true;
    }

    /**
     * @param  list<Range>  $ranges
     * @return list<Range>
     */
    private static function mergeSameStatus(array $ranges): array
    {
        $grouped = [
            ManualAttendanceStatus::HOLIDAY => [],
            ManualAttendanceStatus::REQUIRED_ATTENDANCE => [],
        ];

        foreach ($ranges as $range) {
            $grouped[$range['status']][] = $range;
        }

        $merged = [];
        foreach ($grouped as $group) {
            foreach (self::mergeGroup($group) as $range) {
                $merged[] = $range;
            }
        }

        return $merged;
    }

    /**
     * @param  list<Range>  $group
     * @return list<Range>
     */
    private static function mergeGroup(array $group): array
    {
        if ($group === []) {
            return [];
        }

        usort($group, function (array $a, array $b): int {
            return strcmp($a['starts_on'] ?? '', $b['starts_on'] ?? '');
        });

        $merged = [$group[0]];
        for ($i = 1, $count = count($group); $i < $count; $i++) {
            $lastIndex = count($merged) - 1;
            if (self::overlapsOrAdjacent($merged[$lastIndex], $group[$i])) {
                $merged[$lastIndex] = self::union($merged[$lastIndex], $group[$i]);
            } else {
                $merged[] = $group[$i];
            }
        }

        return $merged;
    }

    /**
     * @param  Range  $a
     * @param  Range  $b
     */
    private static function overlapsOrAdjacent(array $a, array $b): bool
    {
        if ($a['ends_on'] === null || $b['starts_on'] === null) {
            return true;
        }

        return $b['starts_on'] <= self::shift($a['ends_on'], 1);
    }

    /**
     * @param  Range  $a
     * @param  Range  $b
     * @return Range
     */
    private static function union(array $a, array $b): array
    {
        $starts = ($a['starts_on'] === null || $b['starts_on'] === null)
            ? null
            : min($a['starts_on'], $b['starts_on']);

        $ends = ($a['ends_on'] === null || $b['ends_on'] === null)
            ? null
            : max($a['ends_on'], $b['ends_on']);

        return [
            'status' => $a['status'],
            'starts_on' => $starts,
            'ends_on' => $ends,
        ];
    }

    private static function earlierDate(?string $a, ?string $b): ?string
    {
        if ($a === null) {
            return $b;
        }

        if ($b === null) {
            return $a;
        }

        return $a <= $b ? $a : $b;
    }

    private static function shift(string $date, int $days): string
    {
        return Carbon::parse($date)->addDays($days)->toDateString();
    }

    public static function toDateString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Exception) {
            return null;
        }
    }
}
