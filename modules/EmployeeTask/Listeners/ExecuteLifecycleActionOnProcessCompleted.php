<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Listeners;

use Modules\EmployeeTask\DTO\EndTaskDTO;
use Modules\EmployeeTask\DTO\StartTaskDTO;
use Modules\EmployeeTask\Events\EmployeeTaskLifecycleProcessCompleted;
use Modules\EmployeeTask\Models\EmployeeTaskEndRequest;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Models\EmployeeTaskStartRequest;
use Modules\EmployeeTask\Services\EmployeeTaskLifecycleService;
use Modules\Process\Models\Process;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Project\ProjectManagement\Models\ProjectNotificationFine;
use Modules\Project\ProjectManagement\Models\ProjectNotificationFineItem;
use Modules\Project\ProjectManagement\Models\ProjectNotificationLocationConfirmation;
use Modules\Project\ProjectManagement\Models\ProjectNotificationSiteStatusUpdate;
use Modules\Project\ProjectManagement\Models\ProjectNotificationWorkStoppageReason;
use Modules\Project\ProjectManagement\Models\ProjectNotificationWorkStoppageReport;
use Modules\Project\ProjectManagement\Models\ProjectNotificationWorkStoppageReportReason;
use Modules\Project\ProjectManagement\Repositories\ProjectNotificationRepository;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;
use Modules\User\Models\User;

final class ExecuteLifecycleActionOnProcessCompleted
{
    public function __construct(
        private readonly EmployeeTaskLifecycleService $lifecycleService,
        private readonly ProjectNotificationRepository $notificationRepository,
    ) {}

    public function handle(EmployeeTaskLifecycleProcessCompleted $event): void
    {
        $task    = $event->task;
        $process = $event->process;
        $form    = $this->resolveForm($process);

        if ($form === null) {
            return;
        }

        $metadata = $process->metadata ?? [];

        if ($event->approved) {
            $task->load('user');
            $this->executeApprovedAction($task, $form, $metadata);
        } else {
            $this->discardStagedFiles($task, $metadata);
        }

        $this->updateLinkedRequest($process, $event->approved, $metadata['review_notes'] ?? null);
    }

    private function executeApprovedAction(
        EmployeeTaskRequest $task,
        InternalProcessForm $form,
        array $metadata,
    ): void {
        match ($form) {
            InternalProcessForm::StartTask,
            InternalProcessForm::ConfirmProjectNotificationPresence => $this->lifecycleService->performStart(
                $task,
                new StartTaskDTO(
                    latitude:  (float) ($metadata['latitude'] ?? 0),
                    longitude: (float) ($metadata['longitude'] ?? 0),
                    internalProcedureSettingId: null,
                    notes:     $metadata['notes'] ?? null,
                ),
                $task->user ?? User::query()->find($task->user_id),
            ),
            InternalProcessForm::EndTask,
            InternalProcessForm::EndProjectNotificationTask => $this->lifecycleService->performEnd(
                $task,
                new EndTaskDTO(
                    latitude:  (float) ($metadata['latitude'] ?? 0),
                    longitude: (float) ($metadata['longitude'] ?? 0),
                    notes:     $metadata['notes'] ?? null,
                    internalProcedureSettingId: null,
                ),
            ),
            InternalProcessForm::UpdateProjectNotificationTask => $this->applyProjectNotificationUpdate(
                $task,
                $metadata,
            ),
            InternalProcessForm::UpdateProjectNotificationSiteStatus => $this->applyProjectNotificationSiteStatusUpdate(
                $task,
                $process,
                $metadata,
            ),
            InternalProcessForm::ProjectNotificationFine => $this->applyProjectNotificationFine(
                $task,
                $process,
                $metadata,
            ),
            InternalProcessForm::ConfirmProjectNotificationLocation => $this->applyProjectNotificationLocationConfirmation(
                $task,
                $process,
                $metadata,
            ),
            InternalProcessForm::ProjectNotificationWorkStoppageReport => $this->applyProjectNotificationWorkStoppageReport(
                $task,
                $process,
                $metadata,
            ),
            default => null,
        };
    }

    private function updateLinkedRequest(Process $process, bool $approved, ?string $notes): void
    {
        $reviewedBy = $process->steps()
            ->whereNotNull('action_by')
            ->orderByDesc('acted_at')
            ->value('action_by');

        $startRequest = EmployeeTaskStartRequest::query()
            ->where('process_id', $process->id)
            ->first();

        if ($startRequest !== null) {
            $startRequest->update([
                'status'                    => $approved ? 'approved' : 'rejected',
                'reviewed_by'               => $reviewedBy,
                'reviewed_at'               => now(),
                'review_notes'              => $notes,
                'current_procedure_step_id' => null,
            ]);
        }

        $endRequest = EmployeeTaskEndRequest::query()
            ->where('process_id', $process->id)
            ->first();

        if ($endRequest !== null) {
            $endRequest->update([
                'status'                    => $approved ? 'approved' : 'rejected',
                'reviewed_by'               => $reviewedBy,
                'reviewed_at'               => now(),
                'review_notes'              => $notes,
                'current_procedure_step_id' => null,
            ]);
        }
    }

    private function applyProjectNotificationUpdate(
        EmployeeTaskRequest $task,
        array $metadata,
    ): void {
        $notification = $task->projectNotification;
        if ($notification === null) {
            return;
        }

        $update = $metadata['update'] ?? [];
        if ($update === []) {
            return;
        }

        $this->notificationRepository->update($notification->id, $update);
        $this->moveStagedFilesToAttachments($notification, $metadata['files'] ?? []);
    }

    private function applyProjectNotificationSiteStatusUpdate(
        EmployeeTaskRequest $task,
        Process $process,
        array $metadata,
    ): void {
        $notification = $task->projectNotification;
        if ($notification === null) {
            return;
        }

        $update = $metadata['update'] ?? [];
        if ($update === []) {
            return;
        }

        $siteStatusUpdate = ProjectNotificationSiteStatusUpdate::query()->create([
            'company_id' => $notification->company_id,
            'project_notification_id' => $notification->id,
            'employee_task_request_id' => $task->id,
            'process_id' => $process->id,
            'procedure_setting_id' => $process->procedure_setting_id,
            'requested_by' => $metadata['user_id'] ?? null,
            'status' => 'approved',
            ...$update,
        ]);

        $this->moveStagedSiteStatusFilesToAttachments($notification, $siteStatusUpdate, $metadata['files'] ?? []);
    }

    /**
     * @param list<int> $fileIds
     */
    private function moveStagedFilesToAttachments(ProjectNotification $notification, array $fileIds): void
    {
        if ($fileIds === []) {
            return;
        }

        foreach ($notification->getMedia('update_attachments') as $media) {
            if (in_array($media->id, $fileIds, true)) {
                $media->move($notification, 'attachments');
            }
        }
    }

    private function applyProjectNotificationLocationConfirmation(
        EmployeeTaskRequest $task,
        Process $process,
        array $metadata,
    ): void {
        $notification = $task->projectNotification;
        if ($notification === null) {
            return;
        }

        $update = $metadata['update'] ?? [];
        if ($update === []) {
            return;
        }

        ProjectNotificationLocationConfirmation::query()->create([
            'company_id' => $notification->company_id,
            'project_notification_id' => $notification->id,
            'employee_task_request_id' => $task->id,
            'process_id' => $process->id,
            'procedure_setting_id' => $process->procedure_setting_id,
            'requested_by' => $metadata['user_id'] ?? null,
            'status' => 'approved',
            ...$update,
        ]);
    }

    private function applyProjectNotificationFine(
        EmployeeTaskRequest $task,
        Process $process,
        array $metadata,
    ): void {
        $notification = $task->projectNotification;
        if ($notification === null) {
            return;
        }

        $update = $metadata['update'] ?? [];
        if ($update === []) {
            return;
        }

        $fine = ProjectNotificationFine::query()->create([
            'company_id' => $notification->company_id,
            'project_notification_id' => $notification->id,
            'employee_task_request_id' => $task->id,
            'process_id' => $process->id,
            'procedure_setting_id' => $process->procedure_setting_id,
            'requested_by' => $metadata['user_id'] ?? null,
            'status' => 'approved',
            'reason' => $update['reason'] ?? null,
            'total_amount' => $update['total_amount'] ?? 0,
        ]);

        foreach ($update['items'] ?? [] as $index => $item) {
            ProjectNotificationFineItem::query()->create([
                'project_notification_fine_id' => $fine->id,
                'name_ar' => $item['name_ar'],
                'name_en' => $item['name_en'] ?? null,
                'quantity' => $item['quantity'],
                'unit_amount' => $item['unit_amount'],
                'total_amount' => $item['total_amount'],
                'sort_order' => $item['sort_order'] ?? ($index + 1),
            ]);
        }

        $this->moveStagedFineFilesToAttachments($notification, $fine, $metadata['files'] ?? []);
    }

    private function applyProjectNotificationWorkStoppageReport(
        EmployeeTaskRequest $task,
        Process $process,
        array $metadata,
    ): void {
        $notification = $task->projectNotification;
        if ($notification === null) {
            return;
        }

        $update = $metadata['update'] ?? [];
        if ($update === []) {
            return;
        }

        $report = ProjectNotificationWorkStoppageReport::query()->create([
            'company_id' => $notification->company_id,
            'project_notification_id' => $notification->id,
            'employee_task_request_id' => $task->id,
            'process_id' => $process->id,
            'procedure_setting_id' => $process->procedure_setting_id,
            'requested_by' => $metadata['user_id'] ?? null,
            'status' => 'approved',
            'other_notes' => $update['other_notes'] ?? null,
        ]);

        foreach ($update['reasons'] ?? [] as $index => $reason) {
            $reasonModel = ! empty($reason['reason_id'])
                ? ProjectNotificationWorkStoppageReason::query()->find($reason['reason_id'])
                : null;

            ProjectNotificationWorkStoppageReportReason::query()->create([
                'project_notification_work_stoppage_report_id' => $report->id,
                'work_stoppage_reason_id' => $reasonModel?->id,
                'reason_name_ar' => $reasonModel?->name_ar ?? null,
                'reason_name_en' => $reasonModel?->name_en ?? null,
                'notes' => $reason['notes'] ?? null,
                'sort_order' => $reason['sort_order'] ?? ($index + 1),
            ]);
        }

        $this->moveStagedWorkStoppageReportFilesToAttachments($notification, $report, $metadata['files'] ?? []);
    }

    /**
     * @param list<int> $fileIds
     */
    private function moveStagedWorkStoppageReportFilesToAttachments(
        ProjectNotification $notification,
        ProjectNotificationWorkStoppageReport $report,
        array $fileIds,
    ): void {
        if ($fileIds === []) {
            return;
        }

        foreach ($notification->getMedia('work_stoppage_report_attachments') as $media) {
            if (in_array($media->id, $fileIds, true)) {
                $media->move($report, 'attachments');
            }
        }
    }

    /**
     * @param list<int> $fileIds
     */
    private function moveStagedFineFilesToAttachments(
        ProjectNotification $notification,
        ProjectNotificationFine $fine,
        array $fileIds,
    ): void {
        if ($fileIds === []) {
            return;
        }

        foreach ($notification->getMedia('fine_attachments') as $media) {
            if (in_array($media->id, $fileIds, true)) {
                $media->move($fine, 'attachments');
            }
        }
    }

    /**
     * @param list<int> $fileIds
     */
    private function moveStagedSiteStatusFilesToAttachments(
        ProjectNotification $notification,
        ProjectNotificationSiteStatusUpdate $siteStatusUpdate,
        array $fileIds,
    ): void {
        if ($fileIds === []) {
            return;
        }

        foreach ($notification->getMedia('site_status_update_attachments') as $media) {
            if (in_array($media->id, $fileIds, true)) {
                $media->move($siteStatusUpdate, 'attachments');
            }
        }
    }

    /**
     * @param list<int> $fileIds
     */
    private function discardStagedFiles(EmployeeTaskRequest $task, array $metadata): void
    {
        $notification = $task->projectNotification;
        if ($notification === null) {
            return;
        }

        $fileIds = $metadata['files'] ?? [];
        if ($fileIds === []) {
            return;
        }

        $collection = match ($metadata['form'] ?? null) {
            InternalProcessForm::UpdateProjectNotificationSiteStatus->value => 'site_status_update_attachments',
            InternalProcessForm::ProjectNotificationFine->value => 'fine_attachments',
            InternalProcessForm::ProjectNotificationWorkStoppageReport->value => 'work_stoppage_report_attachments',
            default => 'update_attachments',
        };

        foreach ($notification->getMedia($collection) as $media) {
            if (in_array($media->id, $fileIds, true)) {
                $media->delete();
            }
        }
    }

    private function resolveForm(Process $process): ?InternalProcessForm
    {
        $procedureSetting = $process->procedureSetting;
        if ($procedureSetting === null || $procedureSetting->form === null) {
            return null;
        }

        try {
            return InternalProcessForm::from($procedureSetting->form);
        } catch (\ValueError) {
            return null;
        }
    }
}
