<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Time;

use Carbon\CarbonImmutable;
use Modules\Attendance\Domain\Calculator\OvertimeFlags;

/**
 * Computes every clock-in / clock-out boundary for a scheduled period.
 *
 * Pure domain: no IO, no Eloquent, no Carbon::now(). Safe as an Octane singleton.
 *
 * Model (see ATTENDANCE_RULES_V2_IMPLEMENTATION_PLAN.md §5.3):
 *  - Required working minutes are ALWAYS scheduledEnd − scheduledStart (INV-29).
 *  - The early window and the extension window bound ORDINARY working time: only time
 *    inside [workWindowStart, workWindowEnd] counts toward the required hours.
 *  - Each segment overtime flag opens the zone beyond its own window (priced as overtime);
 *    with the flag off the zone is unreachable.
 */
final class ShiftWindowCalculator
{
    public function compute(ShiftWindowInput $in): ShiftWindow
    {
        if ($in->flexibleDay) {
            return $this->computeFlexible($in);
        }

        $flags = $in->overtimeFlags ?? new OvertimeFlags();

        $requiredMinutes = $in->requiredWorkMinutesOverride !== null
            ? max(0, $in->requiredWorkMinutesOverride)
            : (int) $in->scheduledStart->diffInMinutes($in->scheduledEnd, false);
        $maxOtMin        = (int) round($in->maxOverTimeHours * 60);

        $workWindowStart = $in->scheduledStart->subMinutes($in->earlyWindowMinutes);
        $workWindowEnd   = $in->scheduledEnd->addMinutes($in->extensionMinutes);

        $earliestClockIn = $flags->beforeEarlyClockIn
            ? $workWindowStart->subMinutes($maxOtMin)
            : $workWindowStart;

        $lastClockInAt = $flags->afterExtension
            ? $workWindowEnd->addMinutes($maxOtMin)
            : $workWindowEnd;

        $lastClockOutAt = $lastClockInAt;

        $firstClockInDeadline = $in->canClockInBeforeMinutes === null
            ? null
            : $in->scheduledStart->addMinutes($in->canClockInBeforeMinutes);

        $absentAt = $firstClockInDeadline ?? $lastClockInAt;

        // Only ordinary-window time accrues toward the required hours, so the anchor is
        // clamped into the window: an early clock-in accrues from the actual clock-in,
        // a late clock-in from the clock-in itself.
        $anchor = $in->clockIn === null
            ? $in->scheduledStart
            : ($in->clockIn->greaterThan($workWindowStart) ? $in->clockIn : $workWindowStart);

        $remainingMinutes = max(0, $requiredMinutes - $in->alreadyWorkedMinutesInPeriod);

        $expectedClockOutAt = $anchor->addMinutes($remainingMinutes + $in->completedBreakMinutes);
        if ($expectedClockOutAt->greaterThan($lastClockOutAt)) {
            $expectedClockOutAt = $lastClockOutAt;
        }

        $autoCloseTriggerAt = $expectedClockOutAt->addMinutes($maxOtMin);

        return new ShiftWindow(
            requiredWorkMinutes:  $requiredMinutes,
            workWindowStart:      $workWindowStart,
            workWindowEnd:        $workWindowEnd,
            earliestClockIn:      $earliestClockIn,
            firstClockInDeadline: $firstClockInDeadline,
            lastClockInAt:        $lastClockInAt,
            lastClockOutAt:       $lastClockOutAt,
            expectedClockOutAt:   $expectedClockOutAt,
            autoCloseTriggerAt:   $autoCloseTriggerAt,
            absentAt:             $absentAt,
        );
    }

    /**
     * Flexible: clock-in any time during the calendar day. Required hours come from
     * constraint working_hours. Auto-close when those hours complete. If overtime after
     * finish is allowed, a later session can run up to max_over_time.
     */
    private function computeFlexible(ShiftWindowInput $in): ShiftWindow
    {
        $flags = $in->overtimeFlags ?? new OvertimeFlags();
        $requiredMinutes = $in->requiredWorkMinutesOverride !== null
            ? max(0, $in->requiredWorkMinutesOverride)
            : (int) $in->scheduledStart->diffInMinutes($in->scheduledEnd, false);
        $maxOtMin = (int) round($in->maxOverTimeHours * 60);

        $dayStart = $in->scheduledStart;
        $dayEnd = $in->scheduledEnd;
        $alreadyWorked = max(0, $in->alreadyWorkedMinutesInPeriod);
        $remainingMinutes = max(0, $requiredMinutes - $alreadyWorked);
        $hoursComplete = $remainingMinutes === 0 && $alreadyWorked > 0;

        $anchor = $in->clockIn ?? $dayStart;

        if ($hoursComplete) {
            // Overtime re-clock-in after required hours were finished.
            $expectedClockOutAt = $anchor->addMinutes($maxOtMin + $in->completedBreakMinutes);
            if ($expectedClockOutAt->greaterThan($dayEnd)) {
                $expectedClockOutAt = $dayEnd;
            }
            $lastClockInAt = $flags->afterFinishWork ? $dayEnd : $dayStart;
        } else {
            $expectedClockOutAt = $anchor->addMinutes($remainingMinutes + $in->completedBreakMinutes);
            if ($expectedClockOutAt->greaterThan($dayEnd)) {
                $expectedClockOutAt = $dayEnd;
            }
            $lastClockInAt = $dayEnd;
        }

        return new ShiftWindow(
            requiredWorkMinutes:  $requiredMinutes,
            workWindowStart:      $dayStart,
            workWindowEnd:        $dayEnd,
            earliestClockIn:      $dayStart,
            firstClockInDeadline: null,
            lastClockInAt:        $lastClockInAt,
            lastClockOutAt:       $dayEnd,
            expectedClockOutAt:   $expectedClockOutAt,
            autoCloseTriggerAt:   $expectedClockOutAt,
            absentAt:             $dayEnd,
        );
    }
}
