<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Leave\PublicHoliday\Services\GenerateAnnualPublicHolidays;

class GenerateAnnualPublicHolidaysCommand extends Command
{
    protected $signature = 'public-holidays:generate-year {--year= : Year to generate (defaults to the current year)}';

    protected $description = 'Generate recurring branch holidays and applied days for a year without duplicates';

    public function handle(GenerateAnnualPublicHolidays $generator): int
    {
        $year = $this->option('year') ?? now('Asia/Riyadh')->year;
        if (filter_var($year, FILTER_VALIDATE_INT) === false || (int) $year < 1900 || (int) $year > 9998) {
            $this->error('The year must be an integer between 1900 and 9998.');
            return self::FAILURE;
        }

        $count = $generator->execute((int) $year);
        $this->info("Created {$count} holiday(s) for {$year}.");

        return self::SUCCESS;
    }
}
