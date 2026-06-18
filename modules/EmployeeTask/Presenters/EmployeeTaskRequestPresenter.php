<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Presenters;

use Carbon\CarbonImmutable;
use Modules\Attendance\Support\HoursFormatter;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;

final class EmployeeTaskRequestPresenter
{
    public function __construct(private readonly EmployeeTaskRequest $task) {}

    public function toArray(): array
    {
        $task   = $this->task;
        $locale = app()->getLocale();

        return [
            'id'                         => $task->id,
            'serial_number'              => $task->serial_number,
            'title'                      => $task->title,
            'description'                => $task->description,
            'project_id'                 => $task->project_id,
            'approval_responsible_id'    => $task->approval_responsible_id,
            'assignment_responsible_id'  => $task->assignment_responsible_id,
            'duration_hours'             => HoursFormatter::fromDecimalString($task->duration_hours),
            'original_duration_hours'    => $task->original_duration_hours
                ? HoursFormatter::fromDecimalString($task->original_duration_hours)
                : null,
            'task_date'                  => $task->task_date?->format('Y-m-d'),
            'task_location'              => [
                'latitude'      => (float) $task->task_latitude,
                'longitude'     => (float) $task->task_longitude,
                'radius_meters' => $task->radius_meters,
            ],
            'status'                     => $task->status,
            'status_label'               => EmployeeTaskStatus::from($task->status)->label($locale),
            'last_extension_status'      => $task->last_extension_status,
            'last_extension_status_label'=> $this->extensionStatusLabel($task->last_extension_status, $locale),
            'time_from'                  => $task->time_from?->format('Y-m-d H:i:s'),
            'time_to'                    => $task->time_to?->format('Y-m-d H:i:s'),
            'total_task_hours'           => HoursFormatter::fromDecimalString($task->total_task_hours),
            'total_pause_minutes'        => $task->total_pause_minutes,
            'shift_end_method'           => $task->shift_end_method,
            'start_location'             => $task->start_location,
            'end_location'               => $task->end_location,
            'timezone'                   => $task->timezone,
            'notes'                      => $task->notes,
            'rejection_reason'           => $task->rejection_reason,
            'cancellation_reason'        => $task->cancellation_reason,
            'approved_at'                => $task->approved_at?->format('Y-m-d H:i:s'),
            'rejected_at'                => $task->rejected_at?->format('Y-m-d H:i:s'),
            'cancelled_at'               => $task->cancelled_at?->format('Y-m-d H:i:s'),
            'created_at'                 => $task->created_at?->format('Y-m-d H:i:s'),
            'user'                       => $task->relationLoaded('user') && $task->user
                ? ['id' => $task->user->id, 'name' => $task->user->name]
                : null,
            'task_type'                       => $task->relationLoaded('taskType') && $task->taskType
                ? ['id' => $task->taskType->id, 'key' => $task->taskType->key, 'title' => $task->taskType->title]
                : null,
            'current_step'               => $this->presentCurrentStep($task),
            'attachments' => $task->relationLoaded('media')
                ? $task->media->map(fn($media) => [
                    'id'        => $media->id,
                    'url'       => $media->getFullUrl(),
                ])->values()->all()
                : [],
            'sessions'                   => $task->relationLoaded('sessions')
                ? EmployeeTaskSessionPresenter::collection($task->sessions)
                : [],
        ];
    }

    public static function single(EmployeeTaskRequest $task): array
    {
        return (new self($task))->toArray();
    }

    public static function collection(iterable $tasks): array
    {
        $result = [];
        foreach ($tasks as $task) {
            $result[] = (new self($task))->toArray();
        }
        return $result;
    }

    private function presentCurrentStep(EmployeeTaskRequest $task): ?array
    {
        if (!$task->relationLoaded('currentProcedureStep') || !$task->currentProcedureStep) {
            return null;
        }

        $step = $task->currentProcedureStep;

        $actionTakers = [];
        if ($step->relationLoaded('actionTakers') && $step->actionTakers->isNotEmpty()) {
            foreach ($step->actionTakers as $at) {
                $actionTakers[] = [
                    'user_id' => $at->user_id,
                    'name'    => $at->relationLoaded('user') && $at->user ? $at->user->name : null,
                ];
            }
        } elseif ($task->relationLoaded('employeeTaskProcess') && $task->employeeTaskProcess) {
            $process = $task->employeeTaskProcess;
            if ($process->relationLoaded('steps')) {
                $pendingStep = $process->steps->first(fn ($s) => $s->status->value === 'pending');
                if ($pendingStep) {
                    $userIds = $pendingStep->authorized_user_ids ?? [$pendingStep->assigned_user_id];
                    foreach ($userIds as $userId) {
                        $actionTakers[] = [
                            'user_id' => $userId,
                            'name'    => null,
                        ];
                    }
                }
            }
        }

        return [
            'id'           => $step->id,
            'name'         => $step->name,
            'step_order'   => $step->step_order,
            'is_approve'   => (bool) $step->is_approve,
            'action_takers'=> $actionTakers,
        ];
    }

    private function extensionStatusLabel(?string $badge, string $locale): ?string
    {
        if ($badge === null) {
            return null;
        }

        $labels = [
            'extension_pending'  => $locale === 'ar' ? 'في انتظار مراجعة التمديد' : 'Extension Pending',
            'extension_approved' => $locale === 'ar' ? 'تم اعتماد التمديد'         : 'Extension Approved',
            'extension_rejected' => $locale === 'ar' ? 'تم رفض التمديد'            : 'Extension Rejected',
        ];

        return $labels[$badge] ?? $badge;
    }

    public function liveStatus(): array
    {
        $task     = $this->task;
        $timezone = $task->timezone ?: config('app.timezone') ?: 'UTC';

        if (!$task->time_from || !in_array($task->status, EmployeeTaskStatus::activeStatuses(), true)) {
            return ['active_task' => null];
        }

        $timeFrom        = CarbonImmutable::parse($task->time_from, $timezone);
        $now             = CarbonImmutable::now($timezone);
        $durationSeconds = (float) $task->duration_hours * 3600;

        $completedSessionMinutes = $task->relationLoaded('sessions')
            ? $task->sessions->whereNotNull('end_time')->sum('duration_minutes')
            : 0;

        $activeSession = $task->relationLoaded('sessions')
            ? $task->sessions->first(fn ($s) => $s->end_time === null)
            : null;

        $activeSessionSeconds = 0;
        if ($activeSession) {
            $sessionStart         = CarbonImmutable::parse($activeSession->start_time, $timezone);
            $activeSessionSeconds = max(0, $sessionStart->diffInSeconds($now));
        }

        $elapsedSeconds  = ($completedSessionMinutes * 60) + $activeSessionSeconds;
        $remainingSeconds = max(0, (int) $durationSeconds - $elapsedSeconds);
        $progress         = $durationSeconds > 0
            ? min(100, (int) round($elapsedSeconds / $durationSeconds * 100))
            : 0;

        $elapsedFromStart           = max(0, $timeFrom->diffInSeconds($now));
        $timeConsumptionPercentage  = $durationSeconds > 0
            ? min(100, (int) round($elapsedFromStart / $durationSeconds * 100))
            : 0;

        return [
            'active_task' => [
                'task_id'                   => $task->id,
                'title'                     => $task->title,
                'status'                    => $task->status,
                'time_from'                 => $task->time_from?->format('Y-m-d H:i:s'),
                'duration_hours'            => HoursFormatter::fromDecimalString($task->duration_hours),
                'elapsed_seconds'           => $elapsedSeconds,
                'elapsed_formatted'         => $this->formatSeconds($elapsedSeconds),
                'remaining_seconds'         => $remainingSeconds,
                'remaining_formatted'       => $this->formatSeconds($remainingSeconds),
                'progress_percentage'       => $progress,
                'time_consumption_percentage' => $timeConsumptionPercentage,
                'can_request_extension'     => !$task->hasPendingExtension(),
            ],
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
