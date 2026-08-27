<?php

declare(strict_types=1);

namespace Modules\Attendance\Support;

use Carbon\Carbon;

/**
 * The official public-holiday dates that apply to one employee over a date range.
 *
 * Resolved once per request and reused across a whole month, the same way
 * {@see ScheduledWorkDays} is, so a calendar or history page does not run a query per day.
 *
 * Holds the applied days from `public_holiday_days` — not the `date_start .. date_end`
 * range on `public_holidays`. Those differ: `PublicHolidayDayCalculator` shifts a
 * single-day holiday off a weekend and appends compensation days after a multi-day range,
 * so the applied days are the only trustworthy answer to "is the employee off". See INV-21.
 */
final readonly class PublicHolidayDates
{
    /**
     * Note stamped on the attendance rows the removed `attendance:create-holiday-attendance`
     * command and its job used to pre-write. Those rows are no longer authoritative — they
     * were keyed on the company's country, were never cleaned up when a holiday was edited
     * or deleted, and the holiday is now read live instead. A leftover row must therefore
     * not keep a day off on its own, exactly as a stale override row must not
     * ({@see ManualAttendanceStatus::isHolidayRow}).
     */
    public const LEGACY_ROW_NOTE_PREFIX = 'Auto-generated holiday record:';

    /**
     * @param array<string, string> $names Y-m-d => holiday name
     */
    private function __construct(private array $names) {}

    public static function isLegacyGeneratedRow(mixed $notes): bool
    {
        return is_string($notes) && str_starts_with(trim($notes), self::LEGACY_ROW_NOTE_PREFIX);
    }

    public static function none(): self
    {
        return new self([]);
    }

    /**
     * @param array<string, string> $names Y-m-d => holiday name
     */
    public static function fromMap(array $names): self
    {
        return new self($names);
    }

    public function isHoliday(Carbon|string $date): bool
    {
        return $this->nameFor($date) !== null;
    }

    /**
     * The holiday's name, used as the `reason` on the work rules so a client can say why
     * the day is off.
     */
    public function nameFor(Carbon|string $date): ?string
    {
        if ($this->names === []) {
            return null;
        }

        return $this->names[self::toDateString($date)] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->names === [];
    }

    private static function toDateString(Carbon|string $date): string
    {
        if ($date instanceof Carbon) {
            return $date->toDateString();
        }

        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Exception) {
            return '';
        }
    }
}
