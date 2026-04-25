<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\CarbonImmutable;
use Modules\Attendance\DTO\AttendanceDurationResult;

/**
 * Pure duration rules (immutable Carbon internally).
 *
 * - work: clock_in → clock_out (gross; breaks not applied here)
 * - delay: minutes late after {@see $scheduledStart} (0 if on time or early)
 * - overtime: minutes after {@see $scheduledEnd} when clock_out &gt; end (0 otherwise)
 */
final class AttendanceTimeCalculationService
{
    /**
     * @param  mixed  $clockIn  Clock-in instant (Carbon, string, etc.)
     * @param  mixed  $clockOut  Clock-out instant
     * @param  mixed|null  $scheduledStart  Shift start (H:i / H:i:s), optional
     * @param  mixed|null  $scheduledEnd  Shift end (H:i / H:i:s), optional
     */
    public function calculate(
        mixed $clockIn,
        mixed $clockOut,
        mixed $scheduledStart,
        mixed $scheduledEnd,
        string $timezone
    ): AttendanceDurationResult {
        $in = $this->toImmutable($clockIn, $timezone);
        $out = $this->toImmutable($clockOut, $timezone);

        $workMinutes = $out->lessThan($in) ? 0 : (int) $in->diffInMinutes($out, true);

        $startAt = $this->anchorToClockInDate($in, $scheduledStart, $timezone);
        $endAt = $this->anchorToClockInDate($in, $scheduledEnd, $timezone);

        if ($startAt !== null && $endAt !== null && $endAt->lessThanOrEqualTo($startAt)) {
            $endAt = $endAt->addDay();
        }

        $delayMinutes = 0;
        if ($startAt !== null && $in->greaterThan($startAt)) {
            $delayMinutes = (int) $startAt->diffInMinutes($in, true);
        }

        $overtimeMinutes = 0;
        if ($endAt !== null && $out->greaterThan($endAt)) {
            $overtimeMinutes = (int) $endAt->diffInMinutes($out, true);
        }

        $isEarlyDeparture = false;
        $earlyDepartureMinutes = 0;
        if ($endAt !== null && $out->lessThan($endAt)) {
            $isEarlyDeparture = true;
            $earlyDepartureMinutes = (int) $out->diffInMinutes($endAt, true);
        }

        return new AttendanceDurationResult(
            $this->nonNegative($workMinutes),
            $this->nonNegative($delayMinutes),
            $this->nonNegative($overtimeMinutes),
            $isEarlyDeparture,
            $this->nonNegative($earlyDepartureMinutes)
        );
    }

    public function formatHhMm(int $totalMinutes): string
    {
        $m = $this->nonNegative($totalMinutes);
        $h = intdiv($m, 60);
        $min = $m % 60;

        return sprintf('%02d:%02d', $h, $min);
    }

    public function minutesToDecimalHours(int $minutes): float
    {
        return round($this->nonNegative($minutes) / 60, 2);
    }

    private function toImmutable(mixed $value, string $timezone): CarbonImmutable
    {
        if ($value instanceof CarbonImmutable) {
            return $value->setTimezone($timezone);
        }

        return CarbonImmutable::parse($value, $timezone)->setTimezone($timezone);
    }

    private function anchorToClockInDate(
        CarbonImmutable $clockIn,
        mixed $timeOfDay,
        string $timezone
    ): ?CarbonImmutable {
        if ($timeOfDay === null || $timeOfDay === '') {
            return null;
        }

        $t = $this->normalizeToTimeString($timeOfDay);
        if ($t === null) {
            return null;
        }

        $date = $clockIn->toDateString();

        return CarbonImmutable::parse("{$date} {$t}", $timezone);
    }

    private function normalizeToTimeString(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if ($raw instanceof \DateTimeInterface) {
            return \Carbon\Carbon::parse($raw)->format('H:i:s');
        }
        $s = (string) $raw;
        if (str_contains($s, 'T') || str_contains($s, ' ')) {
            return CarbonImmutable::parse($s)->format('H:i:s');
        }
        if (preg_match('/^\d{1,2}:\d{2}$/', $s) === 1) {
            return $s.':00';
        }
        if (preg_match('/^\d{1,2}:\d{2}:\d{2}/', $s) === 1) {
            return $s;
        }

        return null;
    }

    private function nonNegative(int $m): int
    {
        return $m < 0 ? 0 : $m;
    }
}
