<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Models\EmployeeTaskSession;

/**
 * Single writer for all automatic task-close paths.
 *
 * Design mirrors AutoCloseAttendanceService:
 *  - Row-level lock inside a transaction.
 *  - Re-reads status after lock so concurrent callers become no-ops.
 *  - closeAt = pre-computed boundary (NOT now()) — queue delay does not penalise the employee.
 *  - All fields updated in a single UPDATE call.
 *  - Stateless — safe as singleton under Octane/RoadRunner.
 */
final class EmployeeTaskAutoCloseService
{
    /**
     * Atomically close the task if it is still in_progress.
     *
     * @param  EmployeeTaskRequest $task    The row to close (state is re-read inside the lock).
     * @param  CarbonImmutable     $closeAt Stored as time_to — the deterministic boundary time.
     * @param  string              $reason  shift_end_method value: auto_duration | auto_location.
     * @return bool  true = closed; false = already closed or not in_progress (no-op).
     */
    public function closeIfExpired(
        EmployeeTaskRequest $task,
        CarbonImmutable $closeAt,
        string $reason,
    ): bool {
        return DB::transaction(function () use ($task, $closeAt, $reason): bool {
            $fresh = EmployeeTaskRequest::query()
                ->lockForUpdate()
                ->find($task->id);

            if (!$fresh || $fresh->status !== EmployeeTaskStatus::InProgress->value) {
                return false;
            }

            $timezone = $fresh->timezone ?: config('app.timezone') ?: 'Asia/Riyadh';
            $closeAtInTz = $closeAt->setTimezone($timezone);

            $activeSession = EmployeeTaskSession::query()
                ->where('employee_task_request_id', $fresh->id)
                ->whereNull('end_time')
                ->lockForUpdate()
                ->first();

            if ($activeSession) {
                $sessionStart    = CarbonImmutable::parse($activeSession->start_time, $timezone);
                $durationMinutes = max(0, (int) $sessionStart->diffInMinutes($closeAtInTz));

                $activeSession->update([
                    'end_time'         => $closeAtInTz->format('Y-m-d H:i:s'),
                    'duration_minutes' => $durationMinutes,
                    'source'           => $reason,
                ]);
            }

            $totalSessionMinutes = EmployeeTaskSession::query()
                ->where('employee_task_request_id', $fresh->id)
                ->whereNotNull('end_time')
                ->sum('duration_minutes');

            $timeFrom = CarbonImmutable::parse($fresh->time_from, $timezone);
            $totalElapsedMinutes = max(0, (int) $timeFrom->diffInMinutes($closeAtInTz));
            $totalPauseMinutes   = max(0, $totalElapsedMinutes - (int) $totalSessionMinutes);
            $totalTaskHours      = round((int) $totalSessionMinutes / 60, 2);

            $fresh->update([
                'status'             => EmployeeTaskStatus::Completed->value,
                'time_to'            => $closeAtInTz->format('Y-m-d H:i:s'),
                'total_task_hours'   => $totalTaskHours,
                'total_pause_minutes'=> $totalPauseMinutes,
                'shift_end_method'   => $reason,
            ]);

            return true;
        });
    }
}
