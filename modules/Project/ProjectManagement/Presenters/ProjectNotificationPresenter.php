<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Presenters;

use Carbon\Carbon;
use Modules\EmployeeTask\Presenters\EmployeeTaskRequestPresenter;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;
use Modules\Shared\Media\Presenters\MediaPresenter;

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
            'machine_number'              => $n->machine_number,
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
            'permit_source'               => $n->permit_source,
            'permit_recipient'            => $n->permit_recipient,
            'assigned_user_id'            => $n->assigned_user_id,
            'selected_distance_meters'    => $n->selected_distance_meters,
            'status'                      => $n->status,
            'status_label'                => $this->statusLabel($n->status),
            'task_date'                   => $n->task_date?->format('Y-m-d'),
            'task_time'                   => $n->task_time?->format('H:i'),
            'duration_hours'              => $n->duration_hours ? (float) $n->duration_hours : null,
            'notes'                       => $n->notes,
            'approved_by'                 => $n->approved_by,
            'approved_at'                 => $this->formatInTimezone($n->approved_at),
            'rejected_by'                 => $n->rejected_by,
            'rejected_at'                 => $this->formatInTimezone($n->rejected_at),
            'rejection_reason'            => $n->rejection_reason,
            'confirmation_receive_date'   => $this->formatInTimezone($n->confirmation_receive_date),
            'created_by_user_id'          => $n->created_by_user_id,
            'created_at'                  => $this->formatInTimezone($n->created_at),
            'updated_at'                  => $this->formatInTimezone($n->updated_at),
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
            'company'                     => $n->relationLoaded('company') && $n->company
                ? ['id' => $n->company->id, 'name' => $n->company->name]
                : null,
            'company_name'                => $n->relationLoaded('company') && $n->company
                ? $n->company->name
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
                ? MediaPresenter::collection($n->getMedia('attachments'))
                : [],
            'procedure_attachments'      => $this->presentProcedureAttachments($n),
            'last_site_update_status'    => $this->resolveLastSiteUpdateStatus($n),
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
            'task_latitude'               => $n->task_latitude ? (float) $n->task_latitude : null,
            'task_longitude'              => $n->task_longitude ? (float) $n->task_longitude : null,
            'selected_distance_meters'    => $n->selected_distance_meters,
            'confirmation_receive_date'     => $this->formatInTimezone($n->confirmation_receive_date),
            'internal_procedure_setting_id' => $this->resolveInternalProcedureSettingId($n),
            'pending_processes'           => $this->resolvePendingProcesses($n),
            'violations_count'            => 0,
            'created_at'                  => $this->formatInTimezone($n->created_at),
            'company'                     => $n->relationLoaded('company') && $n->company
                ? ['id' => $n->company->id, 'name' => $n->company->name]
                : null,
            'company_name'                => $n->relationLoaded('company') && $n->company
                ? $n->company->name
                : null,
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
            'last_site_update_status'    => $this->resolveLastSiteUpdateStatus($n),
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

    public function toMapArray(): array
    {
        $n = $this->notification;

        return [
            'id'              => $n->id,
            'notification_number' => $n->notification_number,
            'task_name'       => $n->work_description,
            'latitude'        => $n->task_latitude ? (float) $n->task_latitude : null,
            'longitude'       => $n->task_longitude ? (float) $n->task_longitude : null,
            'radius'          => $n->location_radius ? (int) $n->location_radius : null,
            'status'          => $n->status,
            'status_label'    => $this->statusLabel($n->status),
            'assigned_user'   => $n->relationLoaded('assignedUser') && $n->assignedUser
                ? ['id' => $n->assignedUser->id, 'name' => $n->assignedUser->name]
                : null,
            'receive_date'    => $this->formatInMapTimezone($n->confirmation_receive_date),
        ];
    }

    public static function mapCollection(iterable $notifications): array
    {
        $result = [];
        foreach ($notifications as $notification) {
            $result[] = (new self($notification))->toMapArray();
        }
        return $result;
    }

    public static function statusLookup(): array
    {
        $presenter = new self(new ProjectNotification());

        $statuses = ['pending', 'approved', 'rejected', 'in_progress', 'completed', 'cancelled'];
        $result = [];

        foreach ($statuses as $status) {
            $result[] = [
                'key' => $status,
                'label_ar' => $presenter->statusLabel($status, 'ar'),
                'label_en' => $presenter->statusLabel($status, 'en'),
            ];
        }

        return $result;
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

    private function statusLabel(string $status, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        $labels = [
            'pending' => ['ar' => 'بانتظار الرد', 'en' => 'Pending'],
            'approved' => ['ar' => 'مقبول', 'en' => 'Approved'],
            'rejected' => ['ar' => 'مرفوض', 'en' => 'Rejected'],
            'in_progress' => ['ar' => 'تم الاستلام', 'en' => 'Received'],
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
     *   1. ProjectNotification media (attachments collection) → createProjectNotificationTask
     *   2. EmployeeTaskRequest media → createProjectNotificationTask (task-level uploads)
     *   3. EmployeeTaskApprovalRequest media → sendForApproval
     *   4. ProjectNotificationSiteStatusUpdate media → updateProjectNotificationSiteStatus
     *   5. ProjectNotificationFine media → projectNotificationFine
     *   6. ProjectNotificationWorkStoppageReport media → projectNotificationWorkStoppageReport
     *   7. ProjectNotificationWorkResumption media → projectNotificationWorkResumption
     *   8. Staged files on notification (site_status_update_attachments collection) → updateProjectNotificationSiteStatus
     *
     * @return list<array{title: string, attachments: list<array{url: string, name: string}>}>
     */
    private function presentProcedureAttachments(ProjectNotification $n): array
    {
        $groups   = [];
        $formTitle = InternalProcessForm::CreateProjectNotificationTask->labelAr();

        // 1. ProjectNotification's own media (only the 'attachments' collection,
        //    not staged files from other collections like 'site_status_update_attachments').
        if ($n->relationLoaded('media') && $n->media->isNotEmpty()) {
            $createMedia = $n->getMedia('attachments');
            if ($createMedia->isNotEmpty()) {
                $groups[] = [
                    'title'       => $formTitle,
                    'attachments' => $this->formatMediaItems($createMedia),
                ];
            }
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
                        'title'       => app()->getLocale() === 'ar' ? 'إرسال للاعتماد' : 'Send for Approval',
                        'attachments' => $this->formatMediaItems($approval->media),
                    ];
                }
            }
        }

        // 4. Site status updates' media (updateProjectNotificationSiteStatus)
        if ($task->relationLoaded('siteStatusUpdates')) {
            foreach ($task->siteStatusUpdates as $update) {
                if ($update->relationLoaded('media') && $update->media->isNotEmpty()) {
                    $groups[] = [
                        'title'       => InternalProcessForm::UpdateProjectNotificationSiteStatus->labelAr(),
                        'attachments' => $this->formatMediaItems($update->media),
                    ];
                }
            }
        }

        // 5. Fines' media (projectNotificationFine)
        if ($task->relationLoaded('fines')) {
            foreach ($task->fines as $fine) {
                if ($fine->relationLoaded('media') && $fine->media->isNotEmpty()) {
                    $groups[] = [
                        'title'       => InternalProcessForm::ProjectNotificationFine->labelAr(),
                        'attachments' => $this->formatMediaItems($fine->media),
                    ];
                }
            }
        }

        // 6. Work stoppage reports' media (projectNotificationWorkStoppageReport)
        if ($task->relationLoaded('workStoppageReports')) {
            foreach ($task->workStoppageReports as $report) {
                if ($report->relationLoaded('media') && $report->media->isNotEmpty()) {
                    $groups[] = [
                        'title'       => InternalProcessForm::ProjectNotificationWorkStoppageReport->labelAr(),
                        'attachments' => $this->formatMediaItems($report->media),
                    ];
                }
            }
        }

        // 7. Work resumption media (projectNotificationWorkResumption)
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

        // 8. Staged files still on the notification (workflow pending).
        if ($n->relationLoaded('media')) {
            $stagedCollections = [
                'site_status_update_attachments'     => InternalProcessForm::UpdateProjectNotificationSiteStatus,
                'fine_attachments'                   => InternalProcessForm::ProjectNotificationFine,
                'work_stoppage_report_attachments'   => InternalProcessForm::ProjectNotificationWorkStoppageReport,
                'work_resumption_attachments'        => InternalProcessForm::ProjectNotificationWorkResumption,
                'update_attachments'                 => InternalProcessForm::UpdateProjectNotificationTask,
            ];
            foreach ($stagedCollections as $collection => $form) {
                $staged = $n->getMedia($collection);
                if ($staged->isNotEmpty()) {
                    $groups[] = [
                        'title'       => $form->labelAr(),
                        'attachments' => $this->formatMediaItems($staged),
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
        return MediaPresenter::collection($mediaItems);
    }

    /**
     * Convert a UTC datetime to the user's branch timezone.
     * Falls back to the linked task's timezone or the current request's branch timezone.
     */
    private function formatInTimezone(?Carbon $date): ?string
    {
        if (! $date) {
            return null;
        }

        $timezone = $this->notification->employeeTask?->timezone ?? getTimeZoneBranchByRequest();

        return $date->setTimezone($timezone)->format('Y-m-d H:i:s');
    }

    /**
     * Convert a UTC datetime to the assigned user's branch timezone for the map view.
     * Falls back to the current request's branch timezone.
     */
    private function formatInMapTimezone(?Carbon $date): ?string
    {
        if (! $date) {
            return null;
        }

        $timezone = $this->resolveAssignedUserBranchTimezone() ?? getTimeZoneBranchByRequest();

        return $date->setTimezone($timezone)->format('Y-m-d H:i:s');
    }

    private function resolveAssignedUserBranchTimezone(): ?string
    {
        $user = $this->notification->assignedUser;

        if (! $user) {
            return null;
        }

        $timezones = $user->userProfessionalData?->branch?->address?->country?->timezones;

        return $timezones[0]['zoneName'] ?? null;
    }

    /**
     * Resolve the description from the latest site status update, or null if none exists.
     */
    private function resolveLastSiteUpdateStatus(ProjectNotification $n): ?string
    {
        if (! $n->relationLoaded('siteStatusUpdates') || $n->siteStatusUpdates->isEmpty()) {
            return null;
        }

        return $n->siteStatusUpdates->first()->description;
    }
}
