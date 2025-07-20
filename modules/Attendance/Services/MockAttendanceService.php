<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\DTO\ClockInDTO;
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
        private AttendanceService $attendanceService

    ) {}
    public function createDTO(ClockInDTO $clockInDTO,array $rawRequestData)
    {
        $attendance = $this->attendanceService->clockIn($clockInDTO);

        $actualViolations = $this->constraintService->validateAttendance($attendance, $rawRequestData);
        if (!empty($actualViolations)) {
            foreach ($actualViolations as $violationData) {
                if (isset($violationData['constraint_id'])) {
                    $constraint = AttendanceConstraint::find($violationData['constraint_id']);
                    if ($constraint) {
                        $this->constraintService->createViolation($attendance, $constraint, $violationData);
                    }
                }
            }
        }
        return  $attendance;
    }

    public function handleClockInProcess(ClockInDTO $clockInDTO, array $rawRequestData)
    {
        $user = auth()->user();

        $mockAttendanceData = [
            'user_id'             => $user->id,
            'company_id'          => $user->company_id,
            'clock_in_time'       => $clockInDTO->getClockInTime(),
            'timezone'            => getTimeZoneByRequest()  ?? config('app.timezone'),
            'clock_in_location'   => $clockInDTO->getLocation(),
            'ip_address'          => $clockInDTO->getIpAddress(),
            'user_agent'          => $clockInDTO->getUserAgent(),
            'verification_data'   => $rawRequestData['verification_data'] ?? null,
        ];
        $mockAttendance = new \Modules\Attendance\Models\Attendance($mockAttendanceData);

        $mockAttendance->setRelation('user', $user);

        $violations = $this->constraintService->validateAttendance($mockAttendance, $rawRequestData,true);

        return $violations;
    }
}
