<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Leave\PublicHoliday\Models\PublicHoliday;
use Modules\Leave\PublicHoliday\Repositories\PublicHolidayRepository;
use Modules\Leave\PublicHoliday\Services\PublicHolidayDayCalculator;

class RecalculatePublicHolidayDaysCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'holidays:recalculate-days';

    /**
     * The console command description.
     */
    protected $description = 'Recompute public_holiday_days for every existing public holiday using the current PublicHolidayDayCalculator rules';

    public function handle(PublicHolidayDayCalculator $dayCalculator, PublicHolidayRepository $repository): int
    {
        $holidays = PublicHoliday::query()->get();

        $this->info("Recalculating applied days for {$holidays->count()} public holiday(s)...");

        foreach ($holidays as $holiday) {
            $days = $dayCalculator->calculate(
                Carbon::parse($holiday->date_start),
                Carbon::parse($holiday->date_end),
            );

            $repository->syncPublicHolidayDays($holiday, $days);
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
