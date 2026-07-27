<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Time;

use Carbon\CarbonImmutable;

/**
 * All clock-in / clock-out boundaries for one scheduled period. The single source of truth —
 * no service may recompute `start ± minutes` inline (INV-21).
 */
final readonly class ShiftWindow
{
    public function __construct(
        /** Required working minutes — always scheduledEnd − scheduledStart (INV-29). */
        public int $requiredWorkMinutes,
        /** scheduledStart − early window. Ordinary working time begins here. */
        public CarbonImmutable $workWindowStart,
        /** scheduledEnd + extension. Ordinary working time ends here. */
        public CarbonImmutable $workWindowEnd,
        /** Earliest allowed clock-in (extends past workWindowStart when beforeEarlyClockIn flag is on). */
        public CarbonImmutable $earliestClockIn,
        /** First-clock-in deadline (scheduledStart + can_clock_in_before); null = no deadline. */
        public ?CarbonImmutable $firstClockInDeadline,
        /** Latest allowed clock-in (extends past workWindowEnd when afterExtension flag is on). */
        public CarbonImmutable $lastClockInAt,
        /** Latest allowed clock-out; the shift is auto-closed after this. */
        public CarbonImmutable $lastClockOutAt,
        /** When the required working hours complete — stored as clock_out_time on auto-close. */
        public CarbonImmutable $expectedClockOutAt,
        /** When the auto-close job fires (expectedClockOutAt + max overtime). */
        public CarbonImmutable $autoCloseTriggerAt,
        /** When the period flips to absent if the employee never clocked in. */
        public CarbonImmutable $absentAt,
    ) {}

    /**
     * Window boundaries as ISO-8601 strings for API payloads / violation details.
     *
     * @return array<string, string|null>
     */
    public function toResponseArray(): array
    {
        return [
            'work_window_start'        => $this->workWindowStart->toIso8601String(),
            'work_window_end'          => $this->workWindowEnd->toIso8601String(),
            'earliest_clock_in'        => $this->earliestClockIn->toIso8601String(),
            'first_clock_in_deadline'  => $this->firstClockInDeadline?->toIso8601String(),
            'last_clock_in_at'         => $this->lastClockInAt->toIso8601String(),
            'last_clock_out_at'        => $this->lastClockOutAt->toIso8601String(),
            'expected_clock_out_at'    => $this->expectedClockOutAt->toIso8601String(),
            'absent_at'                => $this->absentAt->toIso8601String(),
        ];
    }
}
