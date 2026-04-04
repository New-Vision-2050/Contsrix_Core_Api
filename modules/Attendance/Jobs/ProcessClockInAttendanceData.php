<?php

namespace Modules\Attendance\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AppliedAttendanceConstraint;
use Modules\Attendance\Models\AttendanceConstraint;
use Illuminate\Support\Facades\Log;
use Modules\User\Models\User;

class ProcessClockInAttendanceData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The ID of the attendance record to process.
     * @var string
     */
    protected $attendanceId;

    /**
     * Create a new job instance.
     *
     * @param string $attendanceId The ID of the attendance record.
     * @return void
     */
    public function __construct(string $attendanceId)
    {
        $this->attendanceId = $attendanceId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $attendance = Attendance::where('id', $this->attendanceId)->first();

        if (!$attendance) {
            Log::error("Attendance record {$this->attendanceId} not found.");
            return;
        }
        $trackingPoints = $attendance->location_tracking ?? [];

        $latestPoint = !empty($trackingPoints) ? end($trackingPoints) : $attendance->clock_in_location;

        if($attendance->clock_out_time === null) {
           $user = User::find($attendance->user_id);
           $attendance->update([
                'clock_out_time' => now(),
                'day_status' => 'clocked_out',
                'status' => 'completed',
                'clock_out_location' => $latestPoint,
            ]);
            $attendance->refresh();
            // Calculate and save work hours
            $attendance->calculateWorkHours();

           $constraintService = app(\Modules\Attendance\Services\AttendanceConstraintService::class);
           $constraints = $constraintService->getTodaysWorkRulesForUser($user);

            if (!isset($constraints['first_next_period'])) { 
                Log::warning("No next period found for user {$user->id}");
                return;
            }

            $startTimeNextDay = $constraints['first_next_period']['date'] . ' ' . $constraints['first_next_period']['start_time'].':00';
            $attendanceNextDay = Attendance::where('user_id', $user->id)->where('start_time', '=',$startTimeNextDay )->first();
            if ($attendanceNextDay && $attendanceNextDay->clock_in_time === null) {
                $attendanceNextDay->update([
                    'clock_in_time' => now(),
                    'day_status' => 'in_loction',
                    'status' => Attendance::STATUS_ACTIVE,
                    'clock_in_location' => $attendance->clock_in_location,
                    'is_absent' => 0,
                    'is_holiday' => 0,
                    'end_time' => $constraints['first_next_period']['date'] . ' ' . $constraints['first_next_period']['end_time'] . ':00',
                    'timezone' => $attendance->timezone,
                ]);
            } elseif(!$attendanceNextDay) {

                Attendance::create([
                    'user_id' => $user->id,
                    'company_id' => $attendance->company_id,
                    'timezone' => $attendance->timezone,
                    'start_time' => $startTimeNextDay,
                    'end_time' => $constraints['first_next_period']['date'] . ' ' . $constraints['first_next_period']['end_time'] . ':00',
                    'clock_in_time' => now(),
                    'clock_out_time' => null,
                    'status' => Attendance::STATUS_ACTIVE,
                    'day_status' => 'in_loction',
                    'clock_in_location' => $attendance->clock_in_location,
                ]);

            }
        }

    }
}
