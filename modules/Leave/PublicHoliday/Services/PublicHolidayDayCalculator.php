<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;

class PublicHolidayDayCalculator
{
    /**
     * @return array<int, array{date: Carbon, is_compensation: bool}>
     */
    public function calculate(CarbonInterface $dateStart, CarbonInterface $dateEnd): array
    {
        $start = Carbon::parse($dateStart)->startOfDay();
        $end = Carbon::parse($dateEnd)->startOfDay();

        $days = [];

        foreach (CarbonPeriod::create($start, $end) as $date) {
            $days[] = [
                'date' => Carbon::parse($date)->startOfDay(),
                'is_compensation' => false,
            ];
        }

        return $days;
    }
}
