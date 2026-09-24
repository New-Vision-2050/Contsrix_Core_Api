<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Services;

use Illuminate\Support\Facades\DB;
use Modules\Leave\PublicHoliday\Models\PublicHoliday;
use Modules\Leave\PublicHoliday\Repositories\PublicHolidayRepository;

class GenerateAnnualPublicHolidays
{
    public function __construct(
        private AnnualHolidayDateRange $dateRange,
        private PublicHolidayDayCalculator $dayCalculator,
        private PublicHolidayRepository $repository,
    ) {}

    public function execute(int $year): int
    {
        $created = 0;
        $templates = PublicHoliday::query()->whereNull('recurrence_source_id')
            ->whereNotNull('branch_id')->where('is_recurring', true)
            ->where('is_active', true)->where('year', '<', $year)->cursor();

        foreach ($templates as $template) {
            $holiday = DB::transaction(function () use ($template, $year) {
                // Serialize concurrent runs for the same recurring holiday.
                $source = PublicHoliday::whereKey($template->id)->lockForUpdate()->first();
                if (!$source || !$source->is_active || !$source->is_recurring
                    || PublicHoliday::where('recurrence_source_id', $source->id)->where('year', $year)->exists()) {
                    return null;
                }

                $dates = $this->dateRange->forYear($source->date_start->format('m-d'), $source->date_end->format('m-d'), $year);
                if ($dates === null) {
                    return null;
                }
                [$start, $end] = $dates;
                $holiday = $this->repository->createPublicHoliday([
                    'name' => $source->name,
                    'name_ar' => $source->name_ar,
                    'branch_id' => $source->branch_id,
                    'date_start' => $start->format('Y-m-d'),
                    'date_end' => $end->format('Y-m-d'),
                    'is_recurring' => true,
                    'recurrence_source_id' => $source->id,
                ]);
                $this->repository->syncPublicHolidayDays($holiday, $this->dayCalculator->calculate(
                    \Carbon\Carbon::instance($start), \Carbon\Carbon::instance($end),
                ));

                return $holiday;
            });

            if ($holiday !== null) {
                ++$created;
            }
        }

        return $created;
    }
}
