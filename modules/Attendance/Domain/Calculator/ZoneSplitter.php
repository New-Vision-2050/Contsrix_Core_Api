<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Calculator;

use Carbon\CarbonImmutable;

/**
 * Splits the worked interval [clockIn, clockOut] into the five time zones and attributes
 * completed breaks to the zone they actually overlap.
 *
 * Pure domain: no IO, no Eloquent, no Carbon::now(). Safe as an Octane singleton.
 */
final class ZoneSplitter
{
    public function split(CalculatorInput $input): ZoneMinutes
    {
        if (!$input->clockIn || !$input->clockOut) {
            return new ZoneMinutes(0, 0, 0, 0, 0, $input->totalBreakMinutes);
        }

        $start = $input->scheduledStart;
        $end   = $input->scheduledEnd;

        $windowStart = $start->subMinutes($input->earlyWindowMinutes);
        $windowEnd   = $end->addMinutes($input->extensionMinutes);

        $zones = [
            'outerPre'        => $this->overlap($input->clockIn, $input->clockOut, null, $windowStart),
            'earlyWindow'     => $this->overlap($input->clockIn, $input->clockOut, $windowStart, $start),
            'inShift'         => $this->overlap($input->clockIn, $input->clockOut, $start, $end),
            'extensionWindow' => $this->overlap($input->clockIn, $input->clockOut, $end, $windowEnd),
            'outerPost'       => $this->overlap($input->clockIn, $input->clockOut, $windowEnd, null),
        ];

        $zones = $this->attributeBreaks($input, $zones, $windowStart, $start, $end, $windowEnd);

        return new ZoneMinutes(
            outerPre:        $zones['outerPre'],
            earlyWindow:     $zones['earlyWindow'],
            inShift:         $zones['inShift'],
            extensionWindow: $zones['extensionWindow'],
            outerPost:       $zones['outerPost'],
            totalBreakMinutes: $input->totalBreakMinutes,
        );
    }

    /**
     * Half-open overlap [from, to) with the worked interval, in minutes. A null bound is unbounded.
     */
    private function overlap(
        CarbonImmutable $clockIn,
        CarbonImmutable $clockOut,
        ?CarbonImmutable $from,
        ?CarbonImmutable $to,
    ): int {
        $lo = $from !== null && $from->greaterThan($clockIn) ? $from : $clockIn;
        $hi = $to !== null && $to->lessThan($clockOut) ? $to : $clockOut;

        if (!$hi->greaterThan($lo)) {
            return 0;
        }

        return (int) $lo->diffInMinutes($hi, false);
    }

    /**
     * @param array<string, int> $zones
     * @return array<string, int>
     */
    private function attributeBreaks(
        CalculatorInput $input,
        array $zones,
        CarbonImmutable $windowStart,
        CarbonImmutable $start,
        CarbonImmutable $end,
        CarbonImmutable $windowEnd,
    ): array {
        $bounds = [
            'outerPre'        => [null, $windowStart],
            'earlyWindow'     => [$windowStart, $start],
            'inShift'         => [$start, $end],
            'extensionWindow' => [$end, $windowEnd],
            'outerPost'       => [$windowEnd, null],
        ];

        if ($input->breakIntervals !== []) {
            foreach ($input->breakIntervals as $break) {
                foreach ($bounds as $zone => [$from, $to]) {
                    $minutes = $this->overlap($break['start'], $break['end'], $from, $to);
                    if ($minutes > 0) {
                        $zones[$zone] = max(0, $zones[$zone] - $minutes);
                    }
                }
            }

            return $zones;
        }

        // Legacy rows: break minutes with no intervals. Subtract from the zones an employee
        // would plausibly break in, never from the outer (overtime) zones.
        $remaining = $input->totalBreakMinutes;
        foreach (['inShift', 'extensionWindow', 'earlyWindow'] as $zone) {
            if ($remaining <= 0) {
                break;
            }
            $taken = min($remaining, $zones[$zone]);
            $zones[$zone] -= $taken;
            $remaining -= $taken;
        }

        return $zones;
    }
}
