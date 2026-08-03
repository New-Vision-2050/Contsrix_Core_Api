<?php

declare(strict_types=1);

namespace Modules\Attendance\Support;

use Modules\Attendance\Domain\Calculator\OvertimeFlags;
use Modules\Attendance\Models\AttendanceConstraint;

/**
 * Single reader for every attendance rule, from either a live constraint model or a
 * persisted `applied_attendance_constraints.constraint_snapshot` array.
 *
 * Every rule lives at up to three config paths (per-day weekly_schedule, constraint-level
 * time_rules, legacy flat keys) plus dedicated columns. Never index the JSON directly
 * elsewhere — extend this class instead.
 */
final class ConstraintRuleReader
{
    /** @param array<string, mixed> $constraintArray AttendanceConstraint::toArray() or a constraint_snapshot */
    public function __construct(private readonly array $constraintArray) {}

    public static function fromConstraint(AttendanceConstraint $constraint): self
    {
        return new self($constraint->toArray());
    }

    /** @param array<string, mixed> $snapshot */
    public static function fromSnapshot(array $snapshot): self
    {
        return new self($snapshot);
    }

    /**
     * Lateness grace in minutes. Strict lateness is the default (0) — the grace period no
     * longer suppresses `is_late`; this is retained only for data hygiene reads.
     */
    public function graceMinutes(string $dayName): int
    {
        $rules = $this->dayRules($dayName, 'lateness_rules');

        $value = (int) ($rules['lateness_period'] ?? $rules['grace_period_minutes'] ?? 0);
        if ($value <= 0) {
            return 0;
        }

        $unit = strtolower((string) ($rules['lateness_unit'] ?? $rules['unit'] ?? 'minute'));

        return match ($unit) {
            'hour'  => $value * 60,
            'day'   => $value * 1440,
            default => $value,
        };
    }

    public function earlyClockInMinutes(string $dayName): int
    {
        return EarlyClockInRules::minutes($this->dayRules($dayName, 'early_clock_in_rules'));
    }

    /**
     * Extension window after shift end, in minutes.
     * Prefers `extension_minutes` (canonical); falls back to legacy `extension_hours` × 60.
     */
    public function extensionMinutes(string $dayName): int
    {
        $rules = $this->dayRules($dayName, 'extension_rules');

        if (isset($rules['extension_minutes']) && is_numeric($rules['extension_minutes'])) {
            return max(0, (int) $rules['extension_minutes']);
        }

        // Legacy Rules API stored hours (decimal).
        $hours = (float) ($rules['extension_hours'] ?? 0);

        return max(0, (int) round($hours * 60));
    }

    /**
     * Max overtime cap in minutes (API / FE unit).
     * Column historically stores hours (decimal); convert at the boundary.
     */
    public function maxOverTimeMinutes(): int
    {
        $hours = (float) ($this->constraintArray['max_over_time'] ?? 0.0);

        return max(0, (int) round($hours * 60));
    }

    /**
     * Domain calculator still uses hours (decimal).
     */
    public function maxOverTimeHours(): float
    {
        return (float) ($this->constraintArray['max_over_time'] ?? 0.0);
    }

    /**
     * First-clock-in deadline in minutes from shift start. null = no deadline, no auto-absence.
     */
    public function canClockInBeforeMinutes(string $dayName): ?int
    {
        $rules = $this->dayRules($dayName, 'clock_in_deadline_rules');
        $value = $rules['can_clock_in_before_minutes'] ?? null;

        return $value === null ? null : max(0, (int) $value);
    }

    public function overtimeFlags(): OvertimeFlags
    {
        $timeRules = $this->timeRules();

        return OvertimeFlags::fromArray(is_array($timeRules['overtime_rules'] ?? null) ? $timeRules['overtime_rules'] : []);
    }

    /**
     * @return array<string, mixed>
     */
    private function dayRules(string $dayName, string $key): array
    {
        $dayName = strtolower($dayName);
        $timeRules = $this->timeRules();

        $day = $timeRules['weekly_schedule'][$dayName][$key] ?? null;
        if (is_array($day) && $day !== []) {
            return $day;
        }

        $constraintLevel = $timeRules[$key] ?? null;
        if (is_array($constraintLevel) && $constraintLevel !== []) {
            return $constraintLevel;
        }

        // Legacy flat snapshots (applied_attendance_constraints of older rows).
        $flat = $this->constraintArray[$key] ?? null;

        return is_array($flat) ? $flat : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function timeRules(): array
    {
        $timeRules = $this->constraintArray['constraint_config']['time_rules'] ?? [];

        return is_array($timeRules) ? $timeRules : [];
    }
}
