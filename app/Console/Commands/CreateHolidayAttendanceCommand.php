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
        // Parse the target date in UTC; it is a calendar-date label, not a timestamp.
        $targetDateString = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : Carbon::now('UTC')->toDateString();

        $this->info("Processing holiday attendance for date: {$targetDateString}");

        $holidayDays = PublicHolidayDay::with('publicHoliday')
            ->whereDate('date', $targetDateString)
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

        $companies = Company::with('country')
            ->whereIn('country_id', $countryIds)
            ->get();

        if ($companies->isEmpty()) {
            $this->info('No companies found for the holiday countries.');
            return self::SUCCESS;
        }

        $totalCreated = 0;
        $totalSkipped = 0;

        foreach ($companies as $company) {
            $timezone = $this->resolveCompanyTimezone($company);

            $holidayName = $holidayDays
                ->first(fn ($day) => $day->publicHoliday->country_id === $company->country_id)
                ?->publicHoliday
                ?->getBilingualName() ?? 'Public Holiday';

            $users = User::where('company_id', $company->id)
                ->withoutTenancy()
                ->whereNotIn('email', config('constrix.emails', []))
                ->get();

            $this->info("Company: {$company->name} [{$timezone}] — {$users->count()} user(s) — Holiday: {$holidayName}");

            // startOfDay in the company's own timezone for storing correct start_time.
            $startDateTime = Carbon::parse($targetDateString, $timezone)->startOfDay();

            foreach ($users as $user) {
                $exists = Attendance::where('user_id', $user->id)
                    ->whereDate('start_time', $targetDateString)
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
                    $startDateTime->copy(),
                );

                $totalCreated++;
            }
        }

        $this->info("Done. Created: {$totalCreated} | Skipped (already exist): {$totalSkipped}");

        return self::SUCCESS;
    }

    private function resolveCompanyTimezone(Company $company): string
    {
        $timezones = $company->country?->timezones;
        if (is_array($timezones) && isset($timezones[0]['zoneName']) && is_string($timezones[0]['zoneName'])) {
            return $timezones[0]['zoneName'];
        }

        return config('app.timezone') ?: 'Asia/Riyadh';
    }
}
