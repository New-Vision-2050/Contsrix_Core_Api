<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Leave\PublicHoliday\Models\PublicHoliday;

class HolidayValidationService
{
    /**
     * Check if a specific date is a public holiday
     */
    public function isHoliday(Carbon $date, string $countryId): bool
    {
        return PublicHoliday::active()
            ->forCountry($countryId)
            ->forYear($date->year)
            ->where(function ($query) use ($date) {
                $query->where('date_start', '<=', $date->format('Y-m-d'))
                      ->where('date_end', '>=', $date->format('Y-m-d'));
            })
            ->exists();
    }

    /**
     * Check if a specific date is a public holiday by country code
     */
    public function isHolidayByCountryCode(Carbon $date, string $countryCode): bool
    {
        return PublicHoliday::active()
            ->forCountryCode($countryCode)
            ->forYear($date->year)
            ->where(function ($query) use ($date) {
                $query->where('date_start', '<=', $date->format('Y-m-d'))
                      ->where('date_end', '>=', $date->format('Y-m-d'));
            })
            ->exists();
    }

    /**
     * Get all holidays for a specific date
     */
    public function getHolidaysForDate(Carbon $date, string $countryId): Collection
    {
        return PublicHoliday::active()
            ->forCountry($countryId)
            ->forYear($date->year)
            ->where(function ($query) use ($date) {
                $query->where('date_start', '<=', $date->format('Y-m-d'))
                      ->where('date_end', '>=', $date->format('Y-m-d'));
            })
            ->get();
    }

    /**
     * Get holidays within a date range
     */
    public function getHolidaysInRange(Carbon $startDate, Carbon $endDate, string $countryId): Collection
    {
        return PublicHoliday::active()
            ->forCountry($countryId)
            ->inDateRange($startDate->format('Y-m-d'), $endDate->format('Y-m-d'))
            ->orderBy('date_start')
            ->get();
    }

    /**
     * Get the next working day (skipping holidays and weekends)
     */
    public function getNextWorkingDay(Carbon $date, string $countryId, array $weekends = [0, 6]): Carbon
    {
        $nextDay = $date->copy()->addDay();

        while ($this->isNonWorkingDay($nextDay, $countryId, $weekends)) {
            $nextDay->addDay();
        }

        return $nextDay;
    }

    /**
     * Get the previous working day (skipping holidays and weekends)
     */
    public function getPreviousWorkingDay(Carbon $date, string $countryId, array $weekends = [0, 6]): Carbon
    {
        $prevDay = $date->copy()->subDay();

        while ($this->isNonWorkingDay($prevDay, $countryId, $weekends)) {
            $prevDay->subDay();
        }

        return $prevDay;
    }

    /**
     * Check if a date is a non-working day (holiday or weekend)
     */
    public function isNonWorkingDay(Carbon $date, string $countryId, array $weekends = [0, 6]): bool
    {
        // Check if it's a weekend
        if (in_array($date->dayOfWeek, $weekends)) {
            return true;
        }

        // Check if it's a holiday
        return $this->isHoliday($date, $countryId);
    }

    /**
     * Count working days between two dates
     */
    public function countWorkingDays(Carbon $startDate, Carbon $endDate, string $countryId, array $weekends = [0, 6]): int
    {
        $workingDays = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            if (!$this->isNonWorkingDay($current, $countryId, $weekends)) {
                $workingDays++;
            }
            $current->addDay();
        }

        return $workingDays;
    }

    /**
     * Get upcoming holidays for a country
     */
    public function getUpcomingHolidays(string $countryId, int $days = 30): Collection
    {
        $startDate = Carbon::today();
        $endDate = $startDate->copy()->addDays($days);

        return $this->getHolidaysInRange($startDate, $endDate, $countryId);
    }

    /**
     * Get holidays for a specific year and country
     */
    public function getHolidaysForYear(int $year, string $countryId): Collection
    {
        return PublicHoliday::active()
            ->forCountry($countryId)
            ->forYear($year)
            ->orderBy('date_start')
            ->get();
    }

    /**
     * Get holidays by type for a country and year
     */
    public function getHolidaysByType(string $type, int $year, string $countryId): Collection
    {
        return PublicHoliday::active()
            ->forCountry($countryId)
            ->forYear($year)
            ->byType($type)
            ->orderBy('date_start')
            ->get();
    }

    /**
     * Check if a date range contains any holidays
     */
    public function hasHolidaysInRange(Carbon $startDate, Carbon $endDate, string $countryId): bool
    {
        return PublicHoliday::active()
            ->forCountry($countryId)
            ->inDateRange($startDate->format('Y-m-d'), $endDate->format('Y-m-d'))
            ->exists();
    }

    /**
     * Get holiday statistics for a country and year
     */
    public function getHolidayStats(int $year, string $countryId): array
    {
        $holidays = $this->getHolidaysForYear($year, $countryId);

        $stats = [
            'total_holidays' => $holidays->count(),
            'by_type' => $holidays->groupBy('holiday_type')->map->count(),
            'by_month' => $holidays->groupBy(function ($holiday) {
                return $holiday->date_start->format('F');
            })->map->count(),
            'total_holiday_days' => $holidays->sum('duration'),
            'longest_holiday' => $holidays->max('duration'),
            'shortest_holiday' => $holidays->min('duration'),
        ];

        return $stats;
    }
}
