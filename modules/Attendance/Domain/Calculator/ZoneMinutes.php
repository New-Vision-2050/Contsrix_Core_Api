<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Calculator;

/**
 * Net minutes per zone for a worked interval, breaks already subtracted from the zone
 * they overlap.
 *
 * Zones (all relative to the ordinary-working-time window):
 *  - outerPre:        before (scheduledStart − early window)
 *  - earlyWindow:     [scheduledStart − early window, scheduledStart)
 *  - inShift:         [scheduledStart, scheduledEnd]
 *  - extensionWindow: (scheduledEnd, scheduledEnd + extension]
 *  - outerPost:       after (scheduledEnd + extension)
 */
final readonly class ZoneMinutes
{
    public function __construct(
        public int $outerPre,
        public int $earlyWindow,
        public int $inShift,
        public int $extensionWindow,
        public int $outerPost,
        public int $totalBreakMinutes,
    ) {}

    /** Ordinary working time — counts toward required hours. */
    public function ordinary(): int
    {
        return $this->earlyWindow + $this->inShift + $this->extensionWindow;
    }

    /** Overtime-priced zones (only reachable when the matching flag is on). */
    public function outsideWindow(): int
    {
        return $this->outerPre + $this->outerPost;
    }

    /** Gross net worked minutes across every zone. */
    public function credited(): int
    {
        return $this->ordinary() + $this->outsideWindow();
    }
}
