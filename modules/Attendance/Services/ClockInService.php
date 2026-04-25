<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Modules\Attendance\DTO\ClockInDTO;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Models\Attendance;

/**
 * Use-case entry point for the clock-in flow.
 *
 * Consolidates what was previously split between the controller and MockAttendanceService:
 *  1. Validate the clock-in against work periods and constraints (pre-persist, dry-run).
 *  2. Persist the attendance row and dispatch AttendanceClockedIn (via persistClockIn).
 *
 * Keeping both steps here means the controller stays thin and has one predictable
 * exception type to handle. Stateless — Octane-safe singleton.
 */
final class ClockInService
{
    public function __construct(
        private readonly MockAttendanceService $mockAttendanceService,
    ) {}

    /**
     * @param  ClockInDTO $dto         Validated clock-in data from the FormRequest.
     * @param  array      $requestData Raw/validated request payload forwarded to constraint checks.
     * @return Attendance
     * @throws AttendanceException  When blocked by a constraint violation or already clocked in.
     */
    public function execute(ClockInDTO $dto, array $requestData = []): Attendance
    {
        $violations = $this->mockAttendanceService->validateClockIn($dto, $requestData);

        if (!empty($violations)) {
            throw AttendanceException::clockInBlocked($violations);
        }

        // persistClockIn calls AttendanceService::clockIn() and dispatches AttendanceClockedIn.
        return $this->mockAttendanceService->persistClockIn($dto, $requestData);
    }
}
