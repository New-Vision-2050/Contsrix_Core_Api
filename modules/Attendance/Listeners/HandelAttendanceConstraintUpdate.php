<?php

namespace Modules\Attendance\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Illuminate\Support\Facades\Log;
use Modules\Attendance\Events\AttendanceConstraintUpdated;
use Modules\Attendance\Events\UpdateAttendance;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Services\AutoAttendanceService;
use Modules\User\Models\User;

class HandelAttendanceConstraintUpdate implements ShouldQueue
{
    use InteractsWithQueue;
    private AutoAttendanceService $autoAttendanceService;

    public function __construct(AutoAttendanceService $autoAttendanceService)
    {
        $this->autoAttendanceService = $autoAttendanceService;
    }
    /**
     * Handle the event.
     *
     * @param  UpdateAttendance  $event
     * @return void
     */
    public function handle(UpdateAttendance $event): void
    {
        $updatedConstraintId = $event->constraintId;

        $constraint = AttendanceConstraint::where('id', $updatedConstraintId)->first();

        $userIds = User::whereHas('professionalData',function($q)use($updatedConstraintId){
            $q->where('attendance_constraint_id',$updatedConstraintId);
        })->pluck('id');

        $attendance = Attendance::whereIn('user_id', $userIds)
         ->whereDate('start_time', '>', now()->format('Y-m-d'))
         ->delete();
        $timezone = getTimeZoneBranchByRequest() ?? config('app.timezone');

         foreach ($userIds as $userId) {
            $this->autoAttendanceService->generateAttendanceUsers($constraint->company_id,$userId,now($timezone)->addDay()->startOfDay());
        }
    }
}
