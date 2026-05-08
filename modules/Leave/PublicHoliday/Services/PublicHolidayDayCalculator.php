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

        $duration = (int) ($start->diffInDays($end) + 1);

        if ($duration === 1) {
            return [
                [
                    'date' => $this->getAppliedDateForSingleDay($start),
                    'is_compensation' => false,
                ],
            ];
        }

        $days = [];
        $compensationCount = 0;

        foreach (CarbonPeriod::create($start, $end) as $date) {
            $d = Carbon::parse($date)->startOfDay();
            $days[] = [
                'date' => $d->copy(),
                'is_compensation' => false,
            ];
            if ($d->isFriday() || $d->isSaturday()) {
                ++$compensationCount;
            }
        }

        $nextDay = $end->copy()->addDay();
        for ($i = 0; $i < $compensationCount; ++$i) {
            $days[] = [
                'date' => $nextDay->copy(),
                'is_compensation' => true,
            ];
            $nextDay->addDay();
        }

        return $days;
    }

    private function getAppliedDateForSingleDay(Carbon $date): Carbon
    {
        $d = $date->copy()->startOfDay();

        if ($d->isMonday() || $d->isTuesday() || $d->isWednesday()) {
            return $d->next(Carbon::THURSDAY)->startOfDay();
        }

        if ($d->isFriday() || $d->isSaturday()) {
            return $d->next(Carbon::SUNDAY)->startOfDay();
        }

        // Sunday or Thursday: same day
        return $d;
    }
}
