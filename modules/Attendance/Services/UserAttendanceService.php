<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Services\AttendanceService;
use Modules\User\Models\User;
use Ramsey\Uuid\UuidInterface;

class UserAttendanceService
{
    public function __construct(
        private AttendanceConstraintService $constraintService,
        private AttendanceService $attendanceService
    ) {}

    /**
     * Get today's work rules/constraints for a user
     *
     * @param UuidInterface|string $userId
     * @param string|null $date Optional date (Y-m-d format), defaults to today
     * @return array
     */
    public function getUserConstraintForToday(UuidInterface|string $userId, ?string $date = null): array
    {
        $user = User::find($userId);
        
        if (!$user) {
            throw new \Exception('User not found');
        }

        // If date is provided, use it; otherwise use today
        $workRules = $this->constraintService->getTodaysWorkRulesForUser($user, $date);

        return [
            'user_id' => (string) $user->id,
            'user_name' => $user->name,
            'date' => $date ?? Carbon::now()->format('Y-m-d'),
            'work_rules' => $workRules,
        ];
    }

    /**
     * Check if user is clocked in
     *
     * @param UuidInterface|string $userId
     * @return array
     */
    public function checkClockInStatus(UuidInterface|string $userId): array
    {
        $user = User::find($userId);
        
        if (!$user) {
            throw new \Exception('User not found');
        }

        $attendance = $this->attendanceService->getCurrentAttendance(
            \Ramsey\Uuid\Uuid::fromString((string) $userId)
        );

        $isClockedIn = $attendance !== null && $attendance->isActive();
        $isOnBreak = $attendance ? $attendance->isOnBreak() : false;

        return [
            'user_id' => (string) $user->id,
            'user_name' => $user->name,
            'is_clocked_in' => $isClockedIn,
            'is_on_break' => $isOnBreak,
            'attendance_id' => $attendance ? (string) $attendance->id : null,
            'clock_in_time' => $attendance?->clock_in_time?->format('Y-m-d H:i:s'),
            'status' => $attendance?->status ?? 'not_clocked_in',
        ];
    }
}

