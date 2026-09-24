<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Services;

use DateTime;

class AnnualHolidayDateRange
{
    /** @return array{0: DateTime, 1: DateTime}|null */
    public function forYear(string $start, string $end, int $year): ?array
    {
        $endYear = $end < $start ? $year + 1 : $year;
        [$startMonth, $startDay] = array_map('intval', explode('-', $start));
        [$endMonth, $endDay] = array_map('intval', explode('-', $end));
        if (!checkdate($startMonth, $startDay, $year) || !checkdate($endMonth, $endDay, $endYear)) {
            return null;
        }

        return [new DateTime("{$year}-{$start} 00:00:00"), new DateTime("{$endYear}-{$end} 00:00:00")];
    }

    /** @return array{0: DateTime, 1: DateTime} */
    public function nextValidYear(string $start, string $end, int $year): array
    {
        for ($offset = 0; $offset <= 8; ++$offset) {
            if ($dates = $this->forYear($start, $end, $year + $offset)) {
                return $dates;
            }
        }

        throw new \InvalidArgumentException('Invalid annual holiday dates.');
    }
}
