<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\DTO\ClockInDTO;
use Modules\Attendance\Events\AttendanceClockedIn;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\User\Models\User;

/**
 * A service dedicated to creating non-persisted (mock) Attendance models
 * for the purpose of pre-validation against constraints.
 */
class MockAttendanceService
{
    public function __construct(
        private AttendanceConstraintService $constraintService,
        private AttendanceService $attendanceService,
        private UserAttendanceService $userAttendanceService
    ) {}
    public function createDTO(ClockInDTO $clockInDTO,array $rawRequestData)
    {
        $attendance = $this->attendanceService->clockIn($clockInDTO);

        AttendanceClockedIn::dispatch($attendance->id);

        return  $attendance;
    }

    public function handleClockInProcess(ClockInDTO $clockInDTO, array $rawRequestData)
    {
        $user = auth()->user();

        // Check if user can clock in based on work periods
        $userConstraints = $this->userAttendanceService->getUserConstraints(
            (string) $user->id,
            now()->format('Y-m-d')
        );

        // Check if any active period allows clock in
        $canClockIn = false;
        $activePeriod = null;
        
        if (isset($userConstraints['work_rules']['all_work_periods'])) {
            foreach ($userConstraints['work_rules']['all_work_periods'] as $period) {
                if ($period['can_clock_in'] ?? false) {
                    $canClockIn = true;
                    $activePeriod = $period;
                    break;
                }
            }
        }

        // If no period allows clock in, return violation
        if (!$canClockIn) {
            $reason = 'Cannot clock in at this time.';
            
            if (isset($userConstraints['work_rules']['day_status']) && $userConstraints['work_rules']['day_status'] !== 'work_day') {
                $reason = 'Cannot clock in on non-working day.';
            } elseif ($activePeriod === null) {
                $reason = 'No active work period available for clock in.';
            } else {
                $reason = 'You are already clocked in.';
            }

            return [[
                'type' => 'clock_in_not_allowed',
                'severity' => 'blocking',
                'message' => $reason,
                'details' => [
                    'day_status' => $userConstraints['work_rules']['day_status'] ?? null,
                    'is_holiday' => $userConstraints['work_rules']['is_holiday'] ?? false,
                ]
            ]];
        }

        $mockAttendanceData = [
            'user_id'             => $user->id,
            'clock_in_time'       => $clockInDTO->getClockInTime(),
            'timezone'            => getTimeZoneByRequest()  ?? config('app.timezone'),
            'clock_in_location'   => $clockInDTO->getLocation(),
            'ip_address'          => $clockInDTO->getIpAddress(),
            'user_agent'          => $clockInDTO->getUserAgent(),
            'verification_data'   => $rawRequestData['verification_data'] ?? null,
        ];
        $mockAttendance = new \Modules\Attendance\Models\Attendance($mockAttendanceData);

        $mockAttendance->setRelation('user', $user);

        // Check lateness at clock-in time for the mock attendance
        //$mockAttendance->checkLateness();
        $violations = $this->constraintService->validateAttendance($mockAttendance, $rawRequestData,true);

        return $violations;
    }
}
