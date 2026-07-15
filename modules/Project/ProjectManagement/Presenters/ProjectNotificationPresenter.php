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
            'district'                    => $n->district,
            'full_address'                => $n->full_address,
            'location_radius'             => $n->location_radius,
            'location_link'               => $n->location_link,
            'repair_point'                => $n->repair_point,
            'permit_source'               => $n->permit_source,
            'permit_recipient'            => $n->permit_recipient,
            'assigned_user_ids'           => $n->assigned_user_ids,
            'all_users_can_approve'        => $n->all_users_can_approve,
            'independent_progress'         => $n->independent_progress,
            'selected_distance_meters'    => $n->selected_distance_meters,
            'status'                      => $this->resolvePseudoStatus($n->status, $n->location_confirmed_at !== null, $n->confirmation_receive_date !== null),
            'status_label'                => $this->statusLabel($n->status, null, $n->location_confirmed_at !== null, $n->confirmation_receive_date !== null, $this->resolveUpdateSiteStatusLabel($n), $this->resolveEndTaskStatusLabel($n)),
            'update_site_status'          => $this->formatUpdateSiteStatus($n),
            'end_task_status'             => $this->formatEndTaskStatus($n),
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
            'location_confirmed_at'      => $this->formatInTimezone($n->location_confirmed_at),
            'created_by_user_id'          => $n->created_by_user_id,
            'created_at'                  => $this->formatInTimezone($n->created_at),
            'updated_at'                  => $this->formatInTimezone($n->updated_at),
            'is_read'                     => (bool) $n->getAttribute('is_read'),
            'violations_count'            => 0,
            'assigned_users'              => $this->formatAssignedUsers($n),
            'assigned_user'               => $this->formatFirstAssignedUser($n),
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
            'last_site_update_date'      => $this->resolveLastSiteUpdateDate($n),
            'last_note'                  => $this->formatLastNote($n),
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
            'district'                    => $n->district,
            'full_address'                => $n->full_address,
            'status'                      => $this->resolvePseudoStatus($n->status, $n->location_confirmed_at !== null, $n->confirmation_receive_date !== null),
            'status_label'                => $this->statusLabel($n->status, null, $n->location_confirmed_at !== null, $n->confirmation_receive_date !== null, $this->resolveUpdateSiteStatusLabel($n), $this->resolveEndTaskStatusLabel($n)),
            'task_date'                   => $n->task_date?->format('Y-m-d'),
            'task_time'                   => $n->task_time?->format('H:i'),
            'duration_hours'              => $n->relationLoaded('employeeTask') && $n->employeeTask
                ? ($n->employeeTask->duration_hours ? (float) $n->employeeTask->duration_hours : null)
                : null,
            'task_latitude'               => $n->task_latitude ? (float) $n->task_latitude : null,
            'task_longitude'              => $n->task_longitude ? (float) $n->task_longitude : null,
            'selected_distance_meters'    => $n->selected_distance_meters,
            'confirmation_receive_date'     => $this->formatInTimezone($n->confirmation_receive_date),
            'location_confirmed_at'        => $this->formatInTimezone($n->location_confirmed_at),
            'internal_procedure_setting_id' => $this->resolveInternalProcedureSettingId($n),
            'pending_processes'           => $this->resolvePendingProcesses($n),
            'is_read'                     => (bool) $n->getAttribute('is_read'),
            'violations_count'            => 0,
            'created_at'                  => $this->formatInTimezone($n->created_at),
            'company'                     => $n->relationLoaded('company') && $n->company
                ? ['id' => $n->company->id, 'name' => $n->company->name]
                : null,
            'company_name'                => $n->relationLoaded('company') && $n->company
                ? $n->company->name
                : null,
            'assigned_users'              => $this->formatAssignedUsers($n),
            'assigned_user'               => $this->formatFirstAssignedUser($n),
            'employee_task'               => $n->relationLoaded('employeeTask') && $n->employeeTask
                ? ($fullEmployeeTask
                    ? EmployeeTaskRequestPresenter::single($n->employeeTask)
                    : [
                        'id'             => $n->employeeTask->id,
                        'status'         => $n->employeeTask->status,
                        'serial_number'  => $n->employeeTask->serial_number,
                        'duration_hours' => $n->employeeTask->duration_hours ? (float) $n->employeeTask->duration_hours : null,
                        'user'           => $n->employeeTask->relationLoaded('user') && $n->employeeTask->user
                            ? ['id' => $n->employeeTask->user->id, 'name' => $n->employeeTask->user->name, 'phone' => $n->employeeTask->user->phone]
                            : null,
                    ])
                : null,
            'procedure_attachments'      => $this->presentProcedureAttachments($n),
            'last_site_update_status'    => $this->resolveLastSiteUpdateStatus($n),
            'last_site_update_date'      => $this->resolveLastSiteUpdateDate($n),
            'last_note'                  => $this->formatLastNote($n),
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
            'status'          => $this->resolvePseudoStatus($n->status, $n->location_confirmed_at !== null, $n->confirmation_receive_date !== null),
            'status_label'    => $this->statusLabel($n->status, null, $n->location_confirmed_at !== null, $n->confirmation_receive_date !== null, $this->resolveUpdateSiteStatusLabel($n), $this->resolveEndTaskStatusLabel($n)),
            'assigned_users'  => $this->formatAssignedUsers($n),
            'assigned_user'   => $this->formatFirstAssignedUser($n),
            'receive_date'    => $this->formatInMapTimezone($n->confirmation_receive_date),
            'is_read'         => (bool) $n->getAttribute('is_read'),
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

        $statuses = ['draft', 'pending', 'received', 'confirmed_location', 'completed'];
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

    /**
     * Resolve the pseudo-status code from raw status + flags.
     * Mirrors ProjectNotificationChartsService::resolveStatusCode().
     */
    private static function resolvePseudoStatus(string $status, bool $locationConfirmed, bool $received): string
    {
        if ($status === 'draft') {
            return 'draft';
        }

        if ($status === 'in_progress') {
            return $locationConfirmed ? 'confirmed_location' : 'received';
        }

        if ($status === 'cancelled') {
            if (! $received) {
                return 'pending';
            }
            return $locationConfirmed ? 'confirmed_location' : 'received';
        }

        return $status;
    }

    /**
     * Resolve the display label for a notification status.
     *
     * The "in_progress" and "cancelled" statuses cover employee-facing sub-states:
     *   - Receipt confirmed only (تم الاستلام / Received) — $locationConfirmed = false, $received = true
     *   - Location also confirmed (تم تأكيد الموقع / Confirmed Location) — $locationConfirmed = true
     *   - Never received (قيد الانتظار / Pending) — $received = false (only for cancelled)
     *
     * When a specific notification instance is rendered, the chosen
     * update_site_status is used for in_progress and the chosen end_task_status
     * is used for completed. For completed, the generic "Completed" label is
     * kept only when the chosen end_task_status is "work_completion".
     *
     * $locationConfirmed defaults to true and $received defaults to true so
     * generic/lookup usage (no specific notification instance) resolves to
     * the "Confirmed Location" label for in_progress and "Received" for cancelled.
     */
    private function statusLabel(
        string $status,
        ?string $locale = null,
        bool $locationConfirmed = true,
        bool $received = true,
        ?array $updateSiteStatus = null,
        ?array $endTaskStatus = null,
    ): string {
        $locale ??= app()->getLocale();

        $receivedLabel = ['ar' => 'تم الاستلام', 'en' => 'Received'];
        $confirmedLocationLabel = ['ar' => 'تم تأكيد الموقع', 'en' => 'Confirmed Location'];

        if ($status === 'in_progress' && $updateSiteStatus) {
            return $updateSiteStatus[$locale] ?? $updateSiteStatus['ar'] ?? $updateSiteStatus['en'] ?? $status;
        }

        if ($status === 'completed' && $endTaskStatus) {
            $endTaskStatusKey = $this->notification->endTaskStatus?->key;

            if ($endTaskStatusKey && $endTaskStatusKey !== 'work_completion') {
                return $endTaskStatus[$locale] ?? $endTaskStatus['ar'] ?? $endTaskStatus['en'] ?? $status;
            }
        }

        $labels = [
            'draft' => ['ar' => 'مسودة', 'en' => 'Draft'],
            'pending' => ['ar' => 'بانتظار الرد', 'en' => 'Pending'],
            // Pseudo-status used by statusLookup()/filters to target the
            // "in_progress but location not yet confirmed" sub-state directly.
            'received' => $receivedLabel,
            // Pseudo-status used by statusLookup()/filters to target the
            // "in_progress and location confirmed" sub-state directly.
            'confirmed_location' => $confirmedLocationLabel,
            'in_progress' => $locationConfirmed
                ? $confirmedLocationLabel
                : $receivedLabel,
            'completed' => ['ar' => 'مكتمل', 'en' => 'Completed'],
            // Raw cancelled status: an un-received cancellation is treated as a
            // pending (never-started) notification. Otherwise, show the actual
            // progress sub-state the employee had reached before cancellation.
            'cancelled_raw' => ! $received
                ? ['ar' => 'بانتظار الرد', 'en' => 'Pending']
                : ($locationConfirmed ? $confirmedLocationLabel : $receivedLabel),
        ];

        // Map the raw 'cancelled' status to the conditional entry.
        if ($status === 'cancelled') {
            return $labels['cancelled_raw'][$locale] ?? $status;
        }

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

    /**
     * Format all assigned users as an array of {id, name, phone} objects.
     */
    private function formatAssignedUsers(ProjectNotification $n): array
    {
        return $n->assigned_users->map(fn ($user) => [
            'id'    => $user->id,
            'name'  => $user->name,
            'phone' => $user->phone,
        ])->values()->all();
    }

    /**
     * Format the first assigned user as {id, name, phone} or null.
     */
    private function formatFirstAssignedUser(ProjectNotification $n): ?array
    {
        $user = $n->assigned_user;

        if (! $user) {
            return null;
        }

        return ['id' => $user->id, 'name' => $user->name, 'phone' => $user->phone];
    }

    private function formatLastNote(ProjectNotification $n): ?array
    {
        if (! $n->relationLoaded('notificationNotes') || $n->notificationNotes->isEmpty()) {
            return null;
        }

        $note = $n->notificationNotes->first();
        $user = $note->user;
        $branch = ($user && $user->relationLoaded('userProfessionalData'))
            ? $user->userProfessionalData?->branch
            : null;

        return [
            'id'         => $note->id,
            'note'       => $note->note,
            'created_at' => $this->formatInTimezone($note->created_at),
            'user'       => $user ? [
                'id'    => $user->id,
                'name'  => $user->name,
                'phone' => $user->phone,
            ] : null,
            'branch' => $branch ? [
                'id'   => $branch->id,
                'name' => $branch->name ?? $branch->name_ar ?? null,
            ] : null,
        ];
    }

    private function resolveAssignedUserBranchTimezone(): ?string
    {
        $user = $this->notification->assigned_user;

        if (! $user) {
            return null;
        }

        $timezones = $user->userProfessionalData?->branch?->address?->country?->timezones;

        return $timezones[0]['zoneName'] ?? null;
    }

    private function formatUpdateSiteStatus(ProjectNotification $n): ?array
    {
        $status = $n->relationLoaded('updateSiteStatus') ? $n->updateSiteStatus : null;

        if (! $status) {
            return null;
        }

        return [
            'id' => $status->id,
            'key' => $status->key,
            'name_ar' => $status->name_ar,
            'name_en' => $status->name_en,
        ];
    }

    private function formatEndTaskStatus(ProjectNotification $n): ?array
    {
        $status = $n->relationLoaded('endTaskStatus') ? $n->endTaskStatus : null;

        if (! $status) {
            return null;
        }

        return [
            'id' => $status->id,
            'key' => $status->key,
            'name_ar' => $status->name_ar,
            'name_en' => $status->name_en,
        ];
    }

    /**
     * Build name arrays for the chosen update site status to feed statusLabel().
     *
     * @return array{ar: string, en: string}|null
     */
    private function resolveUpdateSiteStatusLabel(ProjectNotification $n): ?array
    {
        $status = $n->relationLoaded('updateSiteStatus') ? $n->updateSiteStatus : null;

        if (! $status) {
            return null;
        }

        return [
            'ar' => $status->name_ar,
            'en' => $status->name_en,
        ];
    }

    /**
     * Build name arrays for the chosen end task status to feed statusLabel().
     *
     * @return array{ar: string, en: string}|null
     */
    private function resolveEndTaskStatusLabel(ProjectNotification $n): ?array
    {
        $status = $n->relationLoaded('endTaskStatus') ? $n->endTaskStatus : null;

        if (! $status) {
            return null;
        }

        return [
            'ar' => $status->name_ar,
            'en' => $status->name_en,
        ];
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

    /**
     * Resolve the created_at datetime of the latest site status update,
     * formatted in the branch timezone.
     */
    private function resolveLastSiteUpdateDate(ProjectNotification $n): ?string
    {
        if (! $n->relationLoaded('siteStatusUpdates') || $n->siteStatusUpdates->isEmpty()) {
            return null;
        }

        return $this->formatInTimezone($n->siteStatusUpdates->first()->created_at);
    }
}
