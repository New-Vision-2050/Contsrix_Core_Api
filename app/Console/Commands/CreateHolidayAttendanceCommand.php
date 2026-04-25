<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Services\AutoAttendanceService;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Leave\PublicHoliday\Models\PublicHolidayDay;
use Modules\User\Models\User;

class CreateHolidayAttendanceCommand extends Command
{
    protected $signature = 'attendance:create-holiday-attendance
                            {--date= : Optional date in Y-m-d format to process (defaults to today)}';

    protected $description = 'Creates holiday attendance records for all users whose company country matches a public holiday applied date';

    public function __construct(private readonly AutoAttendanceService $autoAttendanceService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $timezone = 'Asia/Riyadh';

        $targetDate = $this->option('date')
            ? Carbon::parse($this->option('date'), $timezone)->startOfDay()
            : Carbon::now($timezone)->startOfDay();

        $this->info("Processing holiday attendance for date: {$targetDate->toDateString()}");

        $holidayDays = PublicHolidayDay::with('publicHoliday')
            ->whereDate('date', $targetDate->toDateString())
            ->whereHas('publicHoliday', fn ($q) => $q->where('is_active', true))
            ->get();

        if ($holidayDays->isEmpty()) {
            $this->info('No active public holidays found for this date.');
            return self::SUCCESS;
        }

        $countryIds = $holidayDays->map(fn ($day) => $day->publicHoliday->country_id)
            ->unique()
            ->filter()
            ->values();

        $this->info("Found {$holidayDays->count()} holiday day(s) across {$countryIds->count()} country/countries.");

        $companies = Company::whereIn('country_id', $countryIds)->get();

        if ($companies->isEmpty()) {
            $this->info('No companies found for the holiday countries.');
            return self::SUCCESS;
        }

        $totalCreated = 0;
        $totalSkipped = 0;

        foreach ($companies as $company) {
            $holidayName = $holidayDays
                ->first(fn ($day) => $day->publicHoliday->country_id === $company->country_id)
                ?->publicHoliday
                ?->getBilingualName() ?? 'Public Holiday';

            $users = User::where('company_id', $company->id)
                ->withoutTenancy()
                ->whereNotIn('email', config('constrix.emails', []))
                ->get();

            $this->info("Company: {$company->name} — {$users->count()} user(s) — Holiday: {$holidayName}");

            foreach ($users as $user) {
                $exists = Attendance::where('user_id', $user->id)
                    ->whereDate('start_time', $targetDate->toDateString())
                    ->where('is_holiday', 1)
                    ->exists();

                if ($exists) {
                    $totalSkipped++;
                    continue;
                }

                $this->autoAttendanceService->createAttendanceRecord(
                    [
                        'user_id'    => $user->id,
                        'company_id' => $company->id,
                        'day_status' => 'holiday',
                        'status'     => 'holiday',
                        'timezone'   => $timezone,
                        'is_holiday' => 1,
                        'notes'      => "Auto-generated holiday record: {$holidayName}",
                    ],
                    $targetDate->copy(),
                );

                $totalCreated++;
            }
        }

        $this->info("Done. Created: {$totalCreated} | Skipped (already exist): {$totalSkipped}");

        return self::SUCCESS;
    }
}
