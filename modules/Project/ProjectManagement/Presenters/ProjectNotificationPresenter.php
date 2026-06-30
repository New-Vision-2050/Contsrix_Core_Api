<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Presenters;

use Modules\EmployeeTask\Presenters\EmployeeTaskRequestPresenter;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;

class ProjectNotificationPresenter
{
    public function __construct(private readonly ProjectNotification $notification) {}

    public function toArray(): array
    {
        $n = $this->notification;

        return [
            'id'                          => $n->id,
            'notification_number'         => $n->notification_number,
            'project_id'                  => $n->project_id,
            'employee_task_request_id'    => $n->employee_task_request_id,
            'notification_type'           => $n->notification_type,
            'severity'                    => $n->severity,
            'work_type'                   => $n->work_type,
            'feeder_number'               => $n->feeder_number,
            'work_description'            => $n->work_description,
            'contractor_id'               => $n->contractor_id,
            'contractor_name'             => $n->contractor_name,
            'contractor_number'           => $n->contractor_number,
            'contractor_technical_number' => $n->contractor_technical_number,
            'contractor_technical_name'   => $n->contractor_technical_name,
            'contractor_category'         => $n->contractor_category,
            'contractor_notes'            => $n->contractor_notes,
            'contractor_mobile'           => $n->contractor_mobile,
            'task_latitude'               => $n->task_latitude ? (float) $n->task_latitude : null,
            'task_longitude'              => $n->task_longitude ? (float) $n->task_longitude : null,
            'location_radius'             => $n->location_radius,
            'location_link'               => $n->location_link,
            'repair_point'                => $n->repair_point,
            'assigned_user_id'            => $n->assigned_user_id,
            'selected_distance_meters'    => $n->selected_distance_meters,
            'status'                      => $n->status,
            'status_label'                => $this->statusLabel($n->status),
            'task_date'                   => $n->task_date?->format('Y-m-d'),
            'task_time'                   => $n->task_time?->format('H:i'),
            'duration_hours'              => $n->duration_hours ? (float) $n->duration_hours : null,
            'notes'                       => $n->notes,
            'approved_by'                 => $n->approved_by,
            'approved_at'                 => $n->approved_at?->format('Y-m-d H:i:s'),
            'rejected_by'                 => $n->rejected_by,
            'rejected_at'                 => $n->rejected_at?->format('Y-m-d H:i:s'),
            'rejection_reason'            => $n->rejection_reason,
            'created_by_user_id'          => $n->created_by_user_id,
            'created_at'                  => $n->created_at?->format('Y-m-d H:i:s'),
            'updated_at'                  => $n->updated_at?->format('Y-m-d H:i:s'),
            'violations_count'            => 0,
            'assigned_user'               => $n->relationLoaded('assignedUser') && $n->assignedUser
                ? ['id' => $n->assignedUser->id, 'name' => $n->assignedUser->name]
                : null,
            'creator'                     => $n->relationLoaded('creator') && $n->creator
                ? ['id' => $n->creator->id, 'name' => $n->creator->name]
                : null,
            'project'                     => $n->relationLoaded('project') && $n->project
                ? ['id' => $n->project->id, 'name' => $n->project->name]
                : null,
            'contractor'                  => $n->relationLoaded('contractor') && $n->contractor
                ? [
                    'id'     => $n->contractor->id,
                    'name'   => $n->contractor->name,
                    'number' => $n->contractor->number,
                    'mobile' => $n->contractor->mobile,
                ]
                : null,
            'employee_task'               => $n->relationLoaded('employeeTask') && $n->employeeTask
                ? EmployeeTaskRequestPresenter::single($n->employeeTask)
                : null,
            'internal_procedure_setting_id' => $this->resolveInternalProcedureSettingId($n),
            'pending_processes'           => $this->resolvePendingProcesses($n),
            'attachments'                 => $n->relationLoaded('media')
                ? $n->media->map(fn($media) => [
                    'id'  => $media->id,
                    'url' => $media->getFullUrl(),
                ])->values()->all()
                : [],
            'procedure_attachments'      => $this->presentProcedureAttachments($n),
        ];
    }

    public function toListArray(bool $fullEmployeeTask = false): array
    {
        $n = $this->notification;

        return [
            'id'                          => $n->id,
            'notification_number'         => $n->notification_number,
            'notification_type'           => $n->notification_type,
            'work_type'                   => $n->work_type,
            'severity'                    => $n->severity,
            'contractor_id'               => $n->contractor_id,
            'contractor_name'             => $n->contractor_name,
            'feeder_number'               => $n->feeder_number,
            'status'                      => $n->status,
            'status_label'                => $this->statusLabel($n->status),
            'task_date'                   => $n->task_date?->format('Y-m-d'),
            'task_time'                   => $n->task_time?->format('H:i'),
            'duration_hours'              => $n->relationLoaded('employeeTask') && $n->employeeTask
                ? ($n->employeeTask->duration_hours ? (float) $n->employeeTask->duration_hours : null)
                : null,
            'selected_distance_meters'    => $n->selected_distance_meters,
            'internal_procedure_setting_id' => $this->resolveInternalProcedureSettingId($n),
            'pending_processes'           => $this->resolvePendingProcesses($n),
            'violations_count'            => 0,
            'created_at'                  => $n->created_at?->format('Y-m-d H:i:s'),
            'assigned_user'               => $n->relationLoaded('assignedUser') && $n->assignedUser
                ? ['id' => $n->assignedUser->id, 'name' => $n->assignedUser->name]
                : null,
            'employee_task'               => $n->relationLoaded('employeeTask') && $n->employeeTask
                ? ($fullEmployeeTask
                    ? EmployeeTaskRequestPresenter::single($n->employeeTask)
                    : [
                        'id'             => $n->employeeTask->id,
                        'status'         => $n->employeeTask->status,
                        'serial_number'  => $n->employeeTask->serial_number,
                        'duration_hours' => $n->employeeTask->duration_hours ? (float) $n->employeeTask->duration_hours : null,
                        'user'           => $n->employeeTask->relationLoaded('user') && $n->employeeTask->user
                            ? ['id' => $n->employeeTask->user->id, 'name' => $n->employeeTask->user->name]
                            : null,
                    ])
                : null,
            'procedure_attachments'      => $this->presentProcedureAttachments($n),
        ];
    }

    public static function single(ProjectNotification $notification): array
    {
        return (new self($notification))->toArray();
    }

    public static function collection(iterable $notifications, bool $fullEmployeeTask = false): array
    {
        $result = [];
        foreach ($notifications as $notification) {
            $result[] = (new self($notification))->toListArray($fullEmployeeTask);
        }
        return $result;
    }

    public static function detail(ProjectNotification $notification): array
    {
        return (new self($notification))->toArray();
    }

    private function resolvePendingProcesses(ProjectNotification $notification): array
    {
        return $notification->getAttribute('pending_processes') ?? [];
    }

    private function resolveInternalProcedureSettingId(ProjectNotification $notification): ?string
    {
        $task = $notification->relationLoaded('employeeTask') ? $notification->employeeTask : null;

        if (! $task) {
            return null;
        }

        $setting = $task->relationLoaded('createProjectNotificationTaskProcedureSetting') ? $task->createProjectNotificationTaskProcedureSetting : null;

        return $setting?->id;
    }

    private function statusLabel(string $status): string
    {
        $locale = app()->getLocale();

        $labels = [
            'pending' => ['ar' => 'بانتظار الرد', 'en' => 'Pending'],
            'approved' => ['ar' => 'مقبول', 'en' => 'Approved'],
            'rejected' => ['ar' => 'مرفوض', 'en' => 'Rejected'],
            'in_progress' => ['ar' => 'قيد التنفيذ', 'en' => 'In Progress'],
            'completed' => ['ar' => 'مكتمل', 'en' => 'Completed'],
            'cancelled' => ['ar' => 'ملغي', 'en' => 'Cancelled'],
        ];

        return $labels[$status][$locale] ?? $status;
    }

    /**
     * Collect all attachments uploaded across every procedure form related to the project notification,
     * grouped by form/procedure title.
     *
     * Sources checked (only when the relationship is loaded, to avoid N+1):
     *   1. ProjectNotification media → createProjectNotificationTask
     *   2. EmployeeTaskRequest media → createProjectNotificationTask (task-level uploads)
     *   3. EmployeeTaskApprovalRequest media → sendForApproval
     *   4. ProjectNotificationWorkResumption media → projectNotificationWorkResumption
     *
     * @return list<array{title: string, attachments: list<array{url: string, name: string}>}>
     */
    private function presentProcedureAttachments(ProjectNotification $n): array
    {
        $groups   = [];
        $locale   = app()->getLocale();
        $formTitle = InternalProcessForm::CreateProjectNotificationTask->labelAr();

        // 1. ProjectNotification's own media
        if ($n->relationLoaded('media') && $n->media->isNotEmpty()) {
            $groups[] = [
                'title'       => $formTitle,
                'attachments' => $this->formatMediaItems($n->media),
            ];
        }

        $task = $n->relationLoaded('employeeTask') ? $n->employeeTask : null;
        if (! $task) {
            return $groups;
        }

        // 2. Task's own media (createProjectNotificationTask uploads)
        if ($task->relationLoaded('media') && $task->media->isNotEmpty()) {
            $groups[] = [
                'title'       => $formTitle,
                'attachments' => $this->formatMediaItems($task->media),
            ];
        }

        // 3. Approval requests' media (sendForApproval)
        if ($task->relationLoaded('approvalRequests')) {
            foreach ($task->approvalRequests as $approval) {
                if ($approval->relationLoaded('media') && $approval->media->isNotEmpty()) {
                    $groups[] = [
                        'title'       => $locale === 'ar' ? 'إرسال للاعتماد' : 'Send for Approval',
                        'attachments' => $this->formatMediaItems($approval->media),
                    ];
                }
            }
        }

        // 4. Work resumption media (projectNotificationWorkResumption)
        if ($task->relationLoaded('workResumptions')) {
            foreach ($task->workResumptions as $resumption) {
                if ($resumption->relationLoaded('media') && $resumption->media->isNotEmpty()) {
                    $groups[] = [
                        'title'       => InternalProcessForm::ProjectNotificationWorkResumption->labelAr(),
                        'attachments' => $this->formatMediaItems($resumption->media),
                    ];
                }
            }
        }

        return $groups;
    }

    /**
     * @param  iterable $mediaItems
     * @return list<array{url: string, name: string}>
     */
    private function formatMediaItems($mediaItems): array
    {
        return collect($mediaItems)
            ->map(fn ($media) => [
                'url'  => $media->getFullUrl(),
                'name' => $media->name ?? $media->file_name,
            ])
            ->values()
            ->all();
    }
}
