<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Modules\Attendance\DTO\ClockOutDTO;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Models\Attendance;

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
    ) {}

    /**
     * @throws AttendanceException  When the user is not clocked in or already clocked out.
     */
    public function execute(ClockOutDTO $dto): Attendance
    {
        return $this->attendanceService->clockOut($dto);
    }
}
