<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Carbon\CarbonImmutable;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Support\HoursFormatter;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Presenters\EmployeeTaskSessionPresenter;

final class EmployeeTaskReportService
{
    public function getIntraDayReport(string $userId, string $date): array
    {
        $attendances = Attendance::query()
            ->where('user_id', $userId)
            ->where('business_date', $date)
            ->with(['breaks'])
            ->get();

        $attendanceTotalMinutes = $attendances->sum('total_work_hours') * 60;

        $tasks = EmployeeTaskRequest::query()
            ->where('user_id', $userId)
            ->whereDate('task_date', $date)
            ->whereIn('status', ['completed', 'in_progress', 'paused'])
            ->with('sessions')
            ->get();

        $completedTasks  = $tasks->where('status', 'completed');
        $taskTotalHours  = (float) $completedTasks->sum('total_task_hours');

        $activeTask = $tasks->whereIn('status', EmployeeTaskStatus::activeStatuses())->first();

        $attendanceTotalHours = $attendances->sum('total_work_hours');
        $totalWorkHours       = (float) $attendanceTotalHours + $taskTotalHours;

        return [
            'data' => [
                'date'     => $date,
                'user_id'  => $userId,
                'attendance_sessions' => $attendances->map(fn ($a) => $this->presentAttendance($a))->values()->all(),
                'task_sessions'       => EmployeeTaskRequest::query()
                    ->where('user_id', $userId)
                    ->whereDate('task_date', $date)
                    ->whereIn('status', ['completed', 'in_progress', 'paused'])
                    ->with('sessions')
                    ->get()
                    ->map(fn ($t) => $this->presentTask($t))
                    ->values()
                    ->all(),
                'active_task' => $activeTask ? $this->presentActiveTask($activeTask) : null,
                'summary' => [
                    'attendance_total_hours' => HoursFormatter::fromDecimalString($attendanceTotalHours),
                    'task_total_hours'        => HoursFormatter::fromHours($taskTotalHours),
                    'total_work_hours'        => HoursFormatter::fromHours($totalWorkHours),
                ],
            ],
        ];
    }

    private function presentAttendance(Attendance $attendance): array
    {
        return [
            'type'             => 'attendance',
            'attendance_id'    => $attendance->id,
            'clock_in_time'    => $attendance->clock_in_time,
            'clock_out_time'   => $attendance->clock_out_time,
            'total_work_hours' => HoursFormatter::fromDecimalString($attendance->total_work_hours),
            'status'           => $attendance->status,
        ];
    }

    private function presentTask(EmployeeTaskRequest $task): array
    {
        $locale = app()->getLocale();

        return [
            'type'                        => 'task',
            'task_id'                     => $task->id,
            'serial_number'               => $task->serial_number,
            'title'                       => $task->title,
            'time_from'                   => $task->time_from?->format('Y-m-d H:i:s'),
            'time_to'                     => $task->time_to?->format('Y-m-d H:i:s'),
            'total_task_hours'            => HoursFormatter::fromDecimalString($task->total_task_hours),
            'duration_hours'              => HoursFormatter::fromDecimalString($task->duration_hours),
            'shift_end_method'            => $task->shift_end_method,
            'status'                      => $task->status,
            'status_label'                => EmployeeTaskStatus::from($task->status)->label($locale),
            'last_extension_status'       => $task->last_extension_status,
            'task_location'               => [
                'latitude'      => (float) $task->task_latitude,
                'longitude'     => (float) $task->task_longitude,
                'radius_meters' => $task->radius_meters,
            ],
            'start_location'  => $task->start_location,
            'end_location'    => $task->end_location,
            'work_sessions'   => EmployeeTaskSessionPresenter::collection($task->sessions),
        ];
    }

    private function presentActiveTask(EmployeeTaskRequest $task): ?array
    {
        if (!$task->time_from) {
            return null;
        }

        $timezone = $task->timezone ?: config('app.timezone') ?: 'Asia/Riyadh';
        $timeFrom = CarbonImmutable::parse($task->time_from, $timezone);
        $now      = CarbonImmutable::now($timezone);

        $durationSeconds = (float) $task->duration_hours * 3600;

        $completedMinutes = $task->sessions
            ->whereNotNull('end_time')
            ->sum('duration_minutes');

        $activeSession = $task->sessions->first(fn ($s) => $s->end_time === null);
        $activeSessionSeconds = 0;

        if ($activeSession) {
            $sessionStart         = CarbonImmutable::parse($activeSession->start_time, $timezone);
            $activeSessionSeconds = max(0, (int) $sessionStart->diffInSeconds($now));
        }

        $elapsedSeconds  = ($completedMinutes * 60) + $activeSessionSeconds;
        $remainingSeconds = max(0, (int) $durationSeconds - $elapsedSeconds);
        $progress         = $durationSeconds > 0 ? min(100, (int) round($elapsedSeconds / $durationSeconds * 100)) : 0;

        $elapsedFromStart           = max(0, (int) $timeFrom->diffInSeconds($now));
        $timeConsumptionPercentage  = $durationSeconds > 0
            ? min(100, (int) round($elapsedFromStart / $durationSeconds * 100))
            : 0;

        return [
            'task_id'                    => $task->id,
            'title'                      => $task->title,
            'status'                     => $task->status,
            'time_from'                  => $task->time_from?->format('Y-m-d H:i:s'),
            'duration_hours'             => HoursFormatter::fromDecimalString($task->duration_hours),
            'elapsed_seconds'            => $elapsedSeconds,
            'elapsed_formatted'          => $this->formatSeconds($elapsedSeconds),
            'remaining_seconds'          => $remainingSeconds,
            'remaining_formatted'        => $this->formatSeconds($remainingSeconds),
            'progress_percentage'        => $progress,
            'time_consumption_percentage'=> $timeConsumptionPercentage,
            'can_request_extension'      => !$task->hasPendingExtension(),
        ];
    }

    private function formatSeconds(int $seconds): string
    {
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
}
