<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Services\AttendanceService;
use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UserAttendanceService
{
    public function __construct(
        private AttendanceConstraintService $constraintService,
        private AttendanceService $attendanceService
    ) {}

    /**
     * Get work rules/constraints for a user
     *
     * @param UuidInterface|string $userId
     * @param string|null $date Optional date (Y-m-d format), defaults to today
     * @return array
     */
    public function getUserConstraints(UuidInterface|string $userId, ?string $date = null): array
    {
        $user = User::findOrFail($userId);

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
     * @throws AttendanceException
     */
    public function checkClockInStatus(UuidInterface|string $userId): array
    {
        try {
            $user = User::findOrFail($userId);

            try {
                $userIdUuid = is_string($userId) ? Uuid::fromString($userId) : $userId;
                $attendance = $this->attendanceService->getCurrentAttendance($userIdUuid);
            } catch (\Exception $e) {
                // If attendance retrieval fails, assume user is not clocked in
                $attendance = null;
            }

            $isClockedIn = $attendance?->isActive() ?? false;
            $isOnBreak = $attendance?->isOnBreak() ?? false;

            return [
                'user_id' => (string) $user->id,
                'user_name' => $user->name,
                'is_clocked_in' => $isClockedIn,
                'is_on_break' => $isOnBreak,
                'attendance_id' => $attendance ? (string) $attendance->id : null,
                'clock_in_time' => $attendance?->clock_in_time?->format('Y-m-d H:i:s'),
                'status' => $attendance?->status ?? 'not_clocked_in',
            ];
        } catch (ModelNotFoundException $e) {
            // Re-throw model not found exceptions
            throw $e;
        } catch (AttendanceException $e) {
            // Re-throw domain exceptions as they should be handled by the controller
            throw $e;
        } catch (\InvalidArgumentException $e) {
            // Re-throw validation exceptions
            throw $e;
        } catch (\Exception $e) {
            // Wrap unexpected exceptions in domain exception
            throw AttendanceException::userNotFound();
        }
    }
}

