<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Presenters;

use Modules\Attendance\Support\HoursFormatter;
use Modules\EmployeeTask\Models\EmployeeTaskApprovalRequest;
use Modules\EmployeeTask\Models\EmployeeTaskEndRequest;
use Modules\EmployeeTask\Models\EmployeeTaskExtensionRequest;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Models\EmployeeTaskStartRequest;
use Modules\Shared\Media\Presenters\MediaPresenter;

/**
 * Normalises every inbox item—task_request, extension_request, task_approval—
 * to the exact same top-level shape so the frontend never has to branch.
 *
 * Shape:
 * {
 *   id, type, type_label, category, category_label,
 *   task: {
 *     id, serial_number, title, task_date, status, status_label,
 *     is_project_notification, project, project_notification
 *   },
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

        $isProjectNotification = (bool) $task->is_project_notification
            || ($task->relationLoaded('projectNotification') && $task->projectNotification);

        $category = $isProjectNotification ? 'project_notification' : 'employee_task';
        $categoryLabel = $locale === 'ar'
            ? ($isProjectNotification ? 'إسناد طوارئ وأعمال' : 'مهمة')
            : ($isProjectNotification ? 'Emergency & Work Assignment' : 'Task');

        return [
            'id'            => $task->id,
            'type'          => 'task_request',
            'type_label'    => $locale === 'ar' ? 'طلب مهمة' : 'Task Request',
            'category'      => $category,
            'category_label' => $categoryLabel,
            'task'          => self::taskSummary($task),
            'employee'      => self::employee($task->relationLoaded('user') ? $task->user : null),
            'status'        => $task->status,
            'current_step'  => self::stepFromProcess($task),
            'summary'       => [
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

    public static function fromEndRequest(EmployeeTaskEndRequest $endRequest): array
    {
        $locale = app()->getLocale();
        $task   = $endRequest->relationLoaded('task') ? $endRequest->task : null;

        return [
            'id'         => $endRequest->id,
            'type'       => 'end_request',
            'type_label' => $locale === 'ar' ? 'طلب انهاء مهمة' : 'End Task Request',
            'task'       => $task ? self::taskSummary($task) : null,
            'employee'   => self::employee(
                $endRequest->relationLoaded('requestedByUser') ? $endRequest->requestedByUser : null
            ),
            'status'     => $endRequest->status,
            'current_step' => self::step($endRequest),
            'summary'    => [
                'notes'     => $endRequest->notes,
                'latitude'  => (float) $endRequest->latitude,
                'longitude' => (float) $endRequest->longitude,
                'time_from' => $task?->time_from?->format('Y-m-d H:i:s'),
            ],
            'created_at' => $endRequest->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    public static function fromStartRequest(EmployeeTaskStartRequest $startRequest): array
    {
        $locale = app()->getLocale();
        $task   = $startRequest->relationLoaded('task') ? $startRequest->task : null;

        return [
            'id'         => $startRequest->id,
            'type'       => 'start_request',
            'type_label' => $locale === 'ar' ? 'طلب بدء مهمة' : 'Start Task Request',
            'task'       => $task ? self::taskSummary($task) : null,
            'employee'   => self::employee(
                $startRequest->relationLoaded('requestedByUser') ? $startRequest->requestedByUser : null
            ),
            'status'     => $startRequest->status,
            'current_step' => self::step($startRequest),
            'summary'    => [
                'notes'     => $startRequest->notes,
                'latitude'  => (float) $startRequest->latitude,
                'longitude' => (float) $startRequest->longitude,
            ],
            'created_at' => $startRequest->created_at?->format('Y-m-d H:i:s'),
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

        $projectNotification = $task->relationLoaded('projectNotification') ? $task->projectNotification : null;
        $project             = $task->relationLoaded('project') ? $task->project : null;

        return [
            'id'            => $task->id,
            'serial_number' => $task->serial_number,
            'title'         => $task->title,
            'task_date'     => $task->task_date?->format('Y-m-d'),
            'status'        => $task->status,
            'status_label'  => \Modules\EmployeeTask\Enums\EmployeeTaskStatus::from($task->status)->label($locale),
            'is_project_notification' => (bool) $task->is_project_notification,
            'project'       => $project ? ['id' => $project->id, 'name' => $project->name] : null,
            'project_notification' => $projectNotification ? [
                'id'                 => $projectNotification->id,
                'notification_number'=> $projectNotification->notification_number,
                'notification_type'  => $projectNotification->notification_type,
                'work_type'          => $projectNotification->work_type,
                'contractor_name'    => $projectNotification->contractor_name,
                'repair_point'       => $projectNotification->repair_point,
                'severity'           => $projectNotification->severity,
            ] : null,
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

    private static function stepFromProcess(EmployeeTaskRequest $task): ?array
    {
        if (!$task->relationLoaded('processes')) {
            return [
                'id'            => null,
                'name'          => null,
                'step_order'    => null,
                'is_approve'    => null,
                'action_takers' => [],
            ];
        }

        $process = $task->processes->first(fn ($p) => $p->status->value === 'in_progress');
        if (!$process || !$process->relationLoaded('steps')) {
            return [
                'id'            => null,
                'name'          => null,
                'step_order'    => null,
                'is_approve'    => null,
                'action_takers' => [],
            ];
        }

        $processStep = $process->steps->first(fn ($s) => $s->status->value === 'pending');
        if (!$processStep) {
            return [
                'id'            => null,
                'name'          => null,
                'step_order'    => null,
                'is_approve'    => null,
                'action_takers' => [],
            ];
        }

        $templateStep = $processStep->relationLoaded('procedureSettingStep')
            ? $processStep->procedureSettingStep
            : null;

        $actionTakers = [];
        $userIds = $processStep->authorized_user_ids ?? [$processStep->assigned_user_id];
        foreach ($userIds as $userId) {
            $name = null;
            if ($processStep->relationLoaded('assignedUser') && $processStep->assignedUser && $processStep->assignedUser->id === $userId) {
                $name = $processStep->assignedUser->name;
            }
            $actionTakers[] = ['user_id' => $userId, 'name' => $name];
        }

        return [
            'id'            => $processStep->step_id,
            'name'          => $templateStep?->name,
            'step_order'    => $processStep->template_step_order,
            'is_approve'    => $templateStep ? (bool) $templateStep->is_approve : null,
            'action_takers' => $actionTakers,
        ];
    }

    private static function step($model): ?array
    {
        if (!$model->relationLoaded('currentProcedureStep') || !$model->currentProcedureStep) {
            return [
                'id'            => null,
                'name'          => null,
                'step_order'    => null,
                'is_approve'    => null,
                'action_takers' => [],
            ];
        }

        $step         = $model->currentProcedureStep;
        $actionTakers = [];

        if ($step->relationLoaded('actionTakers') && $step->actionTakers->isNotEmpty()) {
            foreach ($step->actionTakers as $at) {
                $actionTakers[] = [
                    'user_id' => $at->user_id,
                    'name'    => $at->relationLoaded('user') && $at->user ? $at->user->name : null,
                ];
            }
        } elseif (
            $model->relationLoaded('task')
            && $model->task
            && $model->task->approval_responsible_id
        ) {
            $actionTakers[] = [
                'user_id' => $model->task->approval_responsible_id,
                'name'    => null,
            ];
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
