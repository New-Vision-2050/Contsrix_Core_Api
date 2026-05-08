<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Attendance\Services\AutoAttendanceService;
use Modules\Company\CompanyCore\Models\Company;

class CreateWaitingAttendanceCommand extends Command
{
    protected $signature = 'attendance:create-waiting
                            {--date= : Process a specific date in Y-m-d format (defaults to today)}';

    protected $description = 'Creates waiting attendance records for users who are expected to work on the given date';

    public function __construct(private readonly AutoAttendanceService $autoAttendanceService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $timezone = config('app.timezone', 'Asia/Riyadh');

        $targetDate = $this->option('date')
            ? Carbon::parse($this->option('date'), $timezone)->startOfDay()
            : Carbon::now($timezone)->startOfDay();

        $this->info("Creating waiting records for: {$targetDate->toDateString()}");

        $companies = Company::get();

        foreach ($companies as $company) {
            $this->line("  Processing company: {$company->name} ({$company->id})");
            $this->autoAttendanceService->generateAttendanceUsers(
                $company->id,
                null,
                $targetDate->copy()->startOfDay(),
                $targetDate->copy()->endOfDay(),
            );
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
