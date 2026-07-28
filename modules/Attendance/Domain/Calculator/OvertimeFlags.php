<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Calculator;

/**
 * The three composable overtime toggles (Rules V2).
 *
 * Semantics (D2 in ATTENDANCE_RULES_V2_IMPLEMENTATION_PLAN.md):
 *  - The early-clock-in window and the shift-extension window bound ORDINARY working time.
 *  - Each segment flag opens the zone BEYOND its own window and prices that outer time
 *    as overtime. With the flag off the outer zone is unreachable (clock-in blocked /
 *    shift auto-closed at the window edge).
 *  - `afterFinishWork` composes on top: any surplus over the required hours is overtime.
 */
final readonly class OvertimeFlags
{
    public function __construct(
        public bool $beforeEarlyClockIn = false,
        public bool $afterExtension = false,
        public bool $afterFinishWork = false,
    ) {}

    public function isAnySegmentFlagEnabled(): bool
    {
        return $this->beforeEarlyClockIn || $this->afterExtension;
    }

    /**
     * @param array<string, mixed>|self|null $source
     */
    public static function fromArray(array|self|null $source): self
    {
        if ($source instanceof self) {
            return $source;
        }
        if (!is_array($source)) {
            return new self();
        }

        $bool = static fn (mixed $v): bool => filter_var($v, FILTER_VALIDATE_BOOLEAN);

        return new self(
            beforeEarlyClockIn: $bool($source['is_overtime_before_early_clock_in'] ?? false),
            afterExtension:     $bool(
                $source['is_overtime_after_extension_hours_shift']
                ?? $source['is_overtime_after_extention_hours_shift']  // legacy misspelling alias
                ?? false
            ),
            afterFinishWork:    $bool($source['is_after_finish_working_hours'] ?? false),
        );
    }

    /**
     * @return array{is_overtime_before_early_clock_in: bool, is_overtime_after_extension_hours_shift: bool, is_after_finish_working_hours: bool}
     */
    public function toArray(): array
    {
        return [
            'is_overtime_before_early_clock_in'          => $this->beforeEarlyClockIn,
            'is_overtime_after_extension_hours_shift'    => $this->afterExtension,
            'is_after_finish_working_hours'              => $this->afterFinishWork,
        ];
    }
}
