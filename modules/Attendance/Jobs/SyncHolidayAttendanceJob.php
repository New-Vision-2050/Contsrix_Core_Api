<?php

declare(strict_types=1);

namespace Modules\Attendance\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Services\AutoAttendanceService;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Leave\PublicHoliday\Models\PublicHoliday;
use Modules\User\Models\User;


class SyncHolidayAttendanceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly string $publicHolidayId) {}

    public function handle(AutoAttendanceService $autoAttendanceService): void
    {
        $timezone = 'Asia/Riyadh';
        $today = Carbon::now($timezone)->startOfDay();

        $holiday = PublicHoliday::with('days')->find($this->publicHolidayId);

        if (!$holiday || !$holiday->is_active) {
            Log::info('SyncHolidayAttendanceJob: holiday not found or inactive', [
                'public_holiday_id' => $this->publicHolidayId,
            ]);
            return;
        }

        $upcomingDays = $holiday->days->filter(
            fn ($day) => Carbon::parse($day->date)->startOfDay()->gte($today)
        );

        if ($upcomingDays->isEmpty()) {
            Log::info('SyncHolidayAttendanceJob: no upcoming applied days', [
                'public_holiday_id' => $this->publicHolidayId,
            ]);
            return;
        }

        $companies = Company::where('country_id', $holiday->country_id)->get();

        if ($companies->isEmpty()) {
            Log::info('SyncHolidayAttendanceJob: no companies for country', [
                'country_id' => $holiday->country_id,
            ]);
            return;
        }

        $holidayName = $holiday->getBilingualName();

        $totalCreated = 0;
        $totalSkipped = 0;

        foreach ($companies as $company) {
            $users = User::where('company_id', $company->id)
                ->withoutTenancy()
                ->whereNotIn('email', config('constrix.emails', []))
                ->get();

            foreach ($upcomingDays as $day) {
                $appliedDate = Carbon::parse($day->date, $timezone)->startOfDay();

                foreach ($users as $user) {
                    $exists = Attendance::where('user_id', $user->id)
                        ->whereDate('start_time', $appliedDate->toDateString())
                        ->where('is_holiday', 1)
                        ->exists();

                    if ($exists) {
                        $totalSkipped++;
                        continue;
                    }

                    $autoAttendanceService->createAttendanceRecord(
                        [
                            'user_id'    => $user->id,
                            'company_id' => $company->id,
                            'day_status' => 'holiday',
                            'status'     => 'holiday',
                            'timezone'   => $timezone,
                            'is_holiday' => 1,
                            'notes'      => "Auto-generated holiday record: {$holidayName}",
                        ],
                        $appliedDate->copy(),
                    );

                    $totalCreated++;
                }
            }
        }

        Log::info('SyncHolidayAttendanceJob: completed', [
            'public_holiday_id' => $this->publicHolidayId,
            'holiday_name'      => $holidayName,
            'created'           => $totalCreated,
            'skipped'           => $totalSkipped,
        ]);
    }
}
