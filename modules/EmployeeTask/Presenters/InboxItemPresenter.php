<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Presenters;

use Modules\Attendance\Support\HoursFormatter;
use Modules\EmployeeTask\Models\EmployeeTaskApprovalRequest;
use Modules\EmployeeTask\Models\EmployeeTaskExtensionRequest;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\Shared\Media\Presenters\MediaPresenter;

/**
 * Normalises every inbox item—task_request, extension_request, task_approval—
 * to the exact same top-level shape so the frontend never has to branch.
 *
 * Shape:
 * {
 *   id, type, type_label,
 *   task: { id, serial_number, title, task_date, status, status_label },
 *   employee: { id, name },
 *   status,
 *   current_step: { id, name, step_order, is_approve, action_takers[] },
 *   summary: { ... type-specific ... },
 *   created_at
 * }
 */
final class InboxItemPresenter
{
    public static function fromTaskRequest(EmployeeTaskRequest $task): array
    {
        $locale = app()->getLocale();

        return [
            'id'         => $task->id,
            'type'       => 'task_request',
            'type_label' => $locale === 'ar' ? 'طلب مهمة' : 'Task Request',
            'task'       => self::taskSummary($task),
            'employee'   => self::employee($task->relationLoaded('user') ? $task->user : null),
            'status'     => $task->status,
            'current_step' => self::step($task),
            'summary'    => [
                'duration_hours' => HoursFormatter::fromDecimalString($task->duration_hours),
                'task_date'      => $task->task_date?->format('Y-m-d'),
                'task_location'  => [
                    'latitude'      => (float) $task->task_latitude,
                    'longitude'     => (float) $task->task_longitude,
                    'radius_meters' => $task->radius_meters,
                ],
            ],
            'created_at' => $task->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    public static function fromExtensionRequest(EmployeeTaskExtensionRequest $extension): array
    {
        $locale = app()->getLocale();
        $task   = $extension->relationLoaded('task') ? $extension->task : null;

        return [
            'id'         => $extension->id,
            'type'       => 'extension_request',
            'type_label' => $locale === 'ar' ? 'طلب تمديد' : 'Extension Request',
            'task'       => $task ? self::taskSummary($task) : null,
            'employee'   => self::employee(
                $extension->relationLoaded('requestedByUser') ? $extension->requestedByUser : null
            ),
            'status'     => $extension->status,
            'current_step' => self::step($extension),
            'summary'    => [
                'additional_hours' => HoursFormatter::fromDecimalString($extension->additional_hours),
                'reason'           => $extension->reason,
            ],
            'created_at' => $extension->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    public static function fromApprovalRequest(EmployeeTaskApprovalRequest $approval): array
    {
        $locale = app()->getLocale();
        $task   = $approval->relationLoaded('task') ? $approval->task : null;

        return [
            'id'         => $approval->id,
            'type'       => 'task_approval',
            'type_label' => $locale === 'ar' ? 'طلب اعتماد مهمة' : 'Task Approval Request',
            'task'       => $task ? self::taskSummary($task) : null,
            'employee'   => self::employee(
                $approval->relationLoaded('requestedByUser') ? $approval->requestedByUser : null
            ),
            'status'     => $approval->status,
            'current_step' => self::step($approval),
            'summary'    => [
                'notes'            => $approval->notes,
                'attachments'      => $approval->relationLoaded('media')
                    ? MediaPresenter::collection($approval->getMedia('attachments'))
                    : [],
                'time_from'        => $task?->time_from?->format('Y-m-d H:i:s'),
                'time_to'          => $task?->time_to?->format('Y-m-d H:i:s'),
                'total_task_hours' => $task?->total_task_hours
                    ? HoursFormatter::fromDecimalString($task->total_task_hours)
                    : null,
            ],
            'created_at' => $approval->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    private static function taskSummary(EmployeeTaskRequest $task): array
    {
        $locale = app()->getLocale();

        return [
            'id'            => $task->id,
            'serial_number' => $task->serial_number,
            'title'         => $task->title,
            'task_date'     => $task->task_date?->format('Y-m-d'),
            'status'        => $task->status,
            'status_label'  => \Modules\EmployeeTask\Enums\EmployeeTaskStatus::from($task->status)->label($locale),
        ];
    }

    private static function employee($user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'id'   => $user->id,
            'name' => $user->name,
        ];
    }

    private static function step($model): ?array
    {
        if (!$model->relationLoaded('currentProcedureStep') || !$model->currentProcedureStep) {
            return null;
        }

        $step         = $model->currentProcedureStep;
        $actionTakers = [];

        if ($step->relationLoaded('actionTakers')) {
            foreach ($step->actionTakers as $at) {
                $actionTakers[] = [
                    'user_id' => $at->user_id,
                    'name'    => $at->relationLoaded('user') && $at->user ? $at->user->name : null,
                ];
            }
        }

        return [
            'id'           => $step->id,
            'name'         => $step->name,
            'step_order'   => $step->step_order,
            'is_approve'   => (bool) $step->is_approve,
            'action_takers' => $actionTakers,
        ];
    }
}
