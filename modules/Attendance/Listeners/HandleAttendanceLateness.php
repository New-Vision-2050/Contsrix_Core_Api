<?php

declare(strict_types=1);

namespace Modules\Attendance\Listeners;

use Modules\Attendance\Events\AttendanceClockedIn;
use Modules\Attendance\Models\AppliedAttendanceConstraint;
use Modules\Attendance\Models\Attendance;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
class HandleAttendanceLateness implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  AttendanceClockedIn  $event
     * @return void
     */
    public function handle(AttendanceClockedIn $event): void
    {
        $attendance = Attendance::with('user.professionalData.attendanceConstraint')->find($event->attendanceId);

        $attendance->checkLateness();

        // Get the constraint from the user's professional data
        $constraint = $attendance->user->professionalData->attendanceConstraint;

        if ($constraint) {
            // Create a record in the pivot table with the required fields
            AppliedAttendanceConstraint::create([
                'attendance_id' => $attendance->id,
                'constraint_snapshot' => $constraint->toArray(),
                'company_id' => $attendance->company_id,
            ]);
        }

    }
}
