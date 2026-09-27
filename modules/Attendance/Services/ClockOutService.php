<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Modules\Attendance\DTO\ClockOutDTO;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Repositories\AttendanceRepository;

/**
 * Use-case entry point for the clock-out flow.
 *
 * Wraps AttendanceService::clockOut() as a named, injectable service so the
 * controller can depend on a focused interface instead of the large AttendanceService.
 *
 * Post-clock-out constraint violation logging is kept in the controller for now
 * because it needs access to the raw request data (for device / location checks).
 *
 * Stateless — Octane-safe singleton.
 */
final class ClockOutService
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
        private readonly ClockOutLocationGuard $locationGuard,
        private readonly AttendanceRepository $attendanceRepository,
    ) {}

    /**
     * @throws AttendanceException  When the user is not clocked in, already clocked out,
     *                              or clocking out from outside every allowed location.
     */
    public function execute(ClockOutDTO $dto): Attendance
    {
        // Hard geofence before anything is written: the request GPS must fall inside
        // location_work + additional_locations (same list user-constraint/today shows).
        // Skipped when there is no open shift — clockOut() below throws notClockedIn.
        $attendance = $this->attendanceRepository->getCurrentAttendance($dto->getUserId());
        if ($attendance && $attendance->user) {
            $this->locationGuard->assertAllowed($attendance->user, $attendance, $dto->getLocation());
        }

        return $this->attendanceService->clockOut($dto);
    }
}
