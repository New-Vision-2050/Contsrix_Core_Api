<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Modules\Project\ProjectManagement\Repositories\AttachmentRequestRepository;
use Modules\Project\ProjectManagement\Repositories\ProjectProcedureRepository;
use Modules\Project\ProjectManagement\Models\AttachmentRequest;
use Modules\Project\ProjectManagement\Models\AttachmentRequestItem;
use Modules\Project\ProjectManagement\Models\AttachmentRequestHistory;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Modules\Project\ProjectManagement\Models\ProjectRequirementSubmission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Models\Process;
use Modules\Process\Models\ProcessStep;
use Modules\Shared\Media\Services\FileUploadService;
use Modules\Project\ProjectManagement\Events\AttachmentRequestResponded;
use Modules\Project\ProjectManagement\Models\ProjectProcedureSetting;
use Modules\User\Models\User;

class AttachmentRequestService
{
    public function __construct(
        private AttachmentRequestRepository $repository,
        private ProjectProcedureRepository $projectProcedureRepository,
        private FileUploadService $fileUploadService,
        private AttachmentRequestWorkflowService $workflowService,
        private AttachmentArchiveDeliveryService $archiveDeliveryService,
        private AttachmentRequestVisibilityService $visibilityService,
    ) {
    }

    /**
     * Create a new attachment request
     */
    public function createRequest(array $data): AttachmentRequest
    {
        // Verify project exists and is shared
        $project = ProjectManagement::findOrFail($data['project_id']);
        $projectProcedure = $this->findProjectProcedureOrFail(
            $project,
            (string) $data['procedure_setting_id']
        );

        // Use provided serial number or auto-generate
        $serialNumber = $data['serial_number'] ?? $this->repository->generateSerialNumber();

        $requestData = [
            'serial_number' => $serialNumber,
            'name' => $data['name'],
            'date' => $data['date'],
            'project_id' => $data['project_id'],
            'procedure_setting_id' => $projectProcedure->procedure_setting_id,
            'sender_company_id' => (string) tenant('id'),
            'status' => 'pending',
            'created_by_user_id' => (string) Auth::id(),
            'notes' => $data['notes'] ?? null,
        ];

        $items = $this->prepareAttachmentItems($data['attachments']);

        $request = $this->repository->createWithItems($requestData, $items);

        // Log history
        AttachmentRequestHistory::log(
            requestId: $request->id,
            action: 'request_created',
            description: 'Attachment request created',
            userId: (string) Auth::id(),
            metadata: [
                'request_name' => $request->name,
                'total_attachments' => count($items),
                'procedure_setting_id' => $projectProcedure->procedure_setting_id,
            ]
        );

        $projectProcedure->loadMissing('procedureSetting');

        $activeProcess = null;
        if ($projectProcedure->procedureSetting !== null) {
            $activeProcess = $this->workflowService->startForAttachmentRequest(
                $request,
                $projectProcedure->procedureSetting
            );
        }

        $request = $this->repository->getWithItems($request->id) ?? $request;

        // Decision D4: a procedure with no resolvable steps has no active workflow.
        // Auto-approve and deliver immediately so the request never gets stuck in pending.
        if ($activeProcess === null || ! $this->workflowService->hasActiveWorkflow($request)) {
            return $this->completeWorkflowApproval($request);
        }

        return $request;
    }

    /**
     * Unified inbox: attachment requests AND requirement submissions where the
     * current company is either the sender/uploader (outgoing) or a workflow
     * action-taker (incoming). Both types share the same procedure workflow, so
     * they are merged into one date-sorted, manually paginated feed.
     */
    public function getAllRequests(array $filters = []): LengthAwarePaginatorContract
    {
        $companyId = (string) tenant('id');
        $direction = $filters['direction'] ?? null;
        $perPage = max(1, (int) ($filters['per_page'] ?? 15));
        $page = max(1, (int) request()->query('page', 1));

        $attachmentRequests = $this->repository->getAllRequestsCollection($companyId, $filters);

        // A serial-number search targets attachment requests only; submissions
        // have no serial number, so skip them when `name` is present.
        $submissions = empty($filters['name'])
            ? $this->repository->getRequirementSubmissionsInbox($companyId, $filters, $direction)
            : new Collection();

        if (! empty($filters['type'])) {
            $submissions = $submissions->filter(
                fn (ProjectRequirementSubmission $submission): bool =>
                    $this->submissionInboxStatus($submission) === $filters['type']
            )->values();
        }

        $merged = collect($attachmentRequests->all())
            ->concat($submissions->all())
            ->sortByDesc(static fn ($model) => $model->created_at?->getTimestamp() ?? 0)
            ->values();

        $total = $merged->count();
        $items = $merged->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
    }

    /**
     * Derive a request-style status for a requirement submission from its
     * workflow process, so the unified inbox can be filtered consistently.
     */
    public function submissionInboxStatus(ProjectRequirementSubmission $submission): string
    {
        $process = $submission->relationLoaded('projectRequirementSubmissionProcess')
            ? $submission->projectRequirementSubmissionProcess
            : $submission->projectRequirementSubmissionProcess()->first();

        if ($process === null) {
            return 'approved';
        }

        return match ($process->status) {
            ProcessStatus::Completed => 'approved',
            ProcessStatus::Failed => 'declined',
            default => 'pending',
        };
    }

    /**
     * Get outgoing requests for current company
     */
    public function getOutgoingRequests(?string $projectId = null): Collection
    {
        return $this->repository->getOutgoingRequests(tenant('id'), $projectId);
    }

    /**
     * Get incoming requests for current company
     */
    public function getIncomingRequests(?string $projectId = null): Collection
    {
        return $this->repository->getIncomingRequests(tenant('id'), $projectId);
    }

    /**
     * Get pending incoming requests
     */
    public function getPendingIncoming(?string $projectId = null): Collection
    {
        return $this->repository->getPendingIncoming(tenant('id'), $projectId);
    }

    /**
     * Get request by ID
     */
    public function getRequest(string $requestId): AttachmentRequest
    {
        $request = $this->repository->getWithItems($requestId);

        if (!$request) {
            throw new \Exception('Attachment request not found');
        }

        $companyId = (string) tenant('id');
        $this->visibilityService->assertCompanyCanView($request, $companyId);

        return $request;
    }

    /**
     * List the project_procedure procedure settings linked to a project,
     * for the attachment-request create-form dropdown.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSelectableProcedures(string $projectId): array
    {
        $project = ProjectManagement::withoutGlobalScopes()
            ->where('id', $projectId)
            ->firstOrFail();

        return $this->projectProcedureRepository
            ->listForProject(
                $project->id,
                ProjectProcedureSetting::PROCEDURE_TYPE,
                null,
                $project->company_id,
                $this->readerCompanyId(),
            )
            ->filter(static fn ($pp) => $pp->procedureSetting !== null
                && (bool) $pp->procedureSetting->is_active)
            ->map(static fn ($pp) => [
                'procedure_setting_id' => $pp->procedure_setting_id,
                'name' => $pp->procedureSetting->name,
                'execute_type' => $pp->procedureSetting->execute_type,
                'used_in_document_cycle' => (bool) $pp->used_in_document_cycle,
                'attachment_type' => $pp->attachmentType?->only(['id', 'name']),
                'attachment_sub_type' => $pp->attachmentSubType?->only(['id', 'name']),
                'attachment_sub_sub_type' => $pp->attachmentSubSubType?->only(['id', 'name']),
            ])
            ->values()
            ->all();
    }

    /**
     * Respond to individual attachment item
     */
    public function respondToItem(string $itemId, string $action, ?string $notes = null)
    {
        $item = AttachmentRequestItem::with('attachmentRequest.projectProcedureSetting')->findOrFail($itemId);

        if (! in_array($action, ['approve', 'decline', 'request_update'], true)) {
            throw new \Exception('Invalid action');
        }

        $userId = (string) Auth::id();
        $this->visibilityService->assertCompanyCanView($item->attachmentRequest, (string) tenant('id'));
        $pendingWorkflowStep = null;

        // Decision D6: per-file actions are allowed. When a workflow is active,
        // only a user who owns the current pending step may respond to items.
        // When no workflow is active, restrict to companies related to the request.
        if ($this->workflowService->hasActiveWorkflow($item->attachmentRequest)) {
            $pendingWorkflowStep = $this->workflowService->pendingStepForCurrentUser($item->attachmentRequest);

            if ($pendingWorkflowStep === null) {
                abort(422, 'No pending process step assigned to you for this attachment request.');
            }
        }

        $actionDescriptions = [
            'approve' => 'Attachment approved',
            'decline' => 'Attachment declined',
            'request_update' => 'Update requested for attachment',
        ];

        $actionKeys = [
            'approve' => 'attachment_approved',
            'decline' => 'attachment_declined',
            'request_update' => 'attachment_update_requested',
        ];

        $previousStatus = $item->status;

        return DB::transaction(function () use (
            $item,
            $action,
            $userId,
            $notes,
            $actionKeys,
            $actionDescriptions,
            $previousStatus,
            $pendingWorkflowStep
        ) {
            $workflowMetadata = [];

            switch ($action) {
                case 'approve':
                    $item->approve(
                        userId: $userId,
                        notes: $notes,
                        syncRequestStatus: $pendingWorkflowStep === null
                    );
                    // Save attachment to ArchiveLibrary folder
                    $this->saveAttachmentToFolder($item);
                    AttachmentRequestHistory::deleteMediaReplacementHistoryForApproval(
                        (string) $item->attachment_request_id,
                        (string) $item->id,
                        $userId
                    );

                    if ($pendingWorkflowStep !== null) {
                        $process = $this->workflowService->actOnPendingStepForCurrentUser(
                            $item->attachmentRequest,
                            'approve'
                        );
                        $actedStep = $this->workflowStepFromProcess($process, $pendingWorkflowStep);

                        AttachmentRequestHistory::deleteWorkflowStepLifecycle(
                            requestId: (string) $item->attachment_request_id,
                            process: $process,
                            step: $actedStep
                        );

                        $workflowMetadata = $this->workflowApprovalMetadata($actedStep, $process);
                    }
                    break;
                case 'decline':
                    $item->decline($userId, $notes);
                    break;
                case 'request_update':
                    $item->requestUpdate($userId, $notes);
                    break;
            }

            // Log history with detailed file information
            AttachmentRequestHistory::log(
                requestId: $item->attachment_request_id,
                action: $actionKeys[$action],
                description: $actionDescriptions[$action],
                userId: $userId,
                itemId: $item->id,
                metadata: array_merge([
                    'item_id' => $item->id,
                    'file_name' => $item->file_name,
                    'file_path' => $item->file_path,
                    'file_url' => $item->file_path ? asset('storage/' . $item->file_path) : null,
                    'file_type' => $item->file_type,
                    'file_size' => $item->file_size,
                    'file_size_formatted' => $this->formatFileSize($item->file_size),
                    'status' => $item->status,
                    'response_notes' => $notes,
                    'previous_status' => $previousStatus,
                ], $workflowMetadata)
            );

            return $item->fresh(['respondedByUser', 'attachmentRequest']);
        });
    }

    /**
     * Approve entire request
     */
    public function approveRequest(string $requestId): AttachmentRequest
    {
        $request = $this->findRequestOrFail($requestId);
        $this->visibilityService->assertCompanyCanView($request, (string) tenant('id'));

        if ($this->workflowService->hasActiveWorkflow($request)) {
            $process = $this->workflowService->actOnPendingStepForCurrentUser($request, 'approve');

            if ($process->status === ProcessStatus::Completed && ! $this->workflowService->hasActiveWorkflow($request)) {
                return $this->completeWorkflowApproval($request);
            }

            return $this->repository->getWithItems($request->id) ?? $request->fresh();
        }

        throw new \Exception('No active process found for this attachment request.');
    }

    public function completeWorkflowApproval(AttachmentRequest $request, ?string $userId = null): AttachmentRequest
    {
        $request = $this->repository->getWithItems($request->id) ?? $request;

        if ($request->isApproved()) {
            return $request;
        }

        $userId ??= Auth::check() ? (string) Auth::id() : null;
        $request->loadMissing(['items', 'projectProcedureSetting']);

        // Get all file details before approving
        $filesApproved = $request->items->map(function ($item) {
            return [
                'item_id' => $item->id,
                'file_name' => $item->file_name,
                'file_size' => $item->file_size,
                'file_size_formatted' => $this->formatFileSize($item->file_size),
                'file_type' => $item->file_type,
            ];
        })->toArray();

        $request->approveAll($userId);

        foreach (
            AttachmentRequestItem::with('attachmentRequest')
                ->with('attachmentRequest.projectProcedureSetting')
                ->where('attachment_request_id', $request->id)
                ->get() as $item
        ) {
            $this->saveAttachmentToFolder($item);
        }

        // Log history with all approved files
        AttachmentRequestHistory::log(
            requestId: $request->id,
            action: 'request_approved',
            description: 'Request fully approved - All attachments approved',
            userId: $userId,
            metadata: [
                'total_items' => $request->items->count(),
                'files_approved' => $filesApproved,
            ]
        );

        $request = $this->repository->getWithItems($request->id)
            ?? $request->fresh(['items', 'respondedByUser', 'projectProcedureSetting']);

        // Broadcast notification to sender company users
        $this->broadcastToSenderCompany($request, 'approved');

        return $request;
    }

    /**
     * Decline entire request
     */
    public function declineRequest(string $requestId): AttachmentRequest
    {
        $request = $this->findRequestOrFail($requestId);
        $this->visibilityService->assertCompanyCanView($request, (string) tenant('id'));

        if ($this->workflowService->hasActiveWorkflow($request)) {
            $process = $this->workflowService->actOnPendingStepForCurrentUser($request, 'reject');

            if ($process->status === ProcessStatus::Failed) {
                return $this->completeWorkflowDecline($request);
            }

            if ($process->status === ProcessStatus::Completed && ! $this->workflowService->hasActiveWorkflow($request)) {
                return $this->completeWorkflowApproval($request);
            }

            return $this->repository->getWithItems($request->id) ?? $request->fresh();
        }

        throw new \Exception('No active process found for this attachment request.');
    }

    public function completeWorkflowDecline(AttachmentRequest $request, ?string $userId = null): AttachmentRequest
    {
        $request = $this->repository->getWithItems($request->id) ?? $request;

        if ($request->isDeclined()) {
            return $request;
        }

        $userId ??= Auth::check() ? (string) Auth::id() : null;
        $request->loadMissing('items');

        // Get all file details before declining
        $filesDeclined = $request->items->map(function ($item) {
            return [
                'item_id' => $item->id,
                'file_name' => $item->file_name,
                'file_size' => $item->file_size,
                'file_size_formatted' => $this->formatFileSize($item->file_size),
                'file_type' => $item->file_type,
            ];
        })->toArray();

        $request->declineAll($userId);

        // Log history with all declined files
        AttachmentRequestHistory::log(
            requestId: $request->id,
            action: 'request_declined',
            description: 'Request declined - All attachments declined',
            userId: $userId,
            metadata: [
                'total_items' => $request->items->count(),
                'files_declined' => $filesDeclined,
            ]
        );

        $request = $this->repository->getWithItems($request->id)
            ?? $request->fresh(['items', 'respondedByUser', 'projectProcedureSetting']);

        // Broadcast notification to sender company users
        $this->broadcastToSenderCompany($request, 'declined');

        return $request;
    }

    /**
     * Prepare attachment items from uploaded files
     */
    private function prepareAttachmentItems(array $attachments): array
    {
        $items = [];

        foreach ($attachments as $attachment) {
            $items[] = [
                'file_name' => $attachment->getClientOriginalName(),
                'file_path' => null, // Will be populated by media library
                'file_type' => $attachment->getClientMimeType(),
                'file_size' => $attachment->getSize(),
                'status' => 'pending',
                'uploaded_file' => $attachment, // Store for media library processing
            ];
        }

        return $items;
    }

    private function findProjectProcedureOrFail(
        ProjectManagement $project,
        string $procedureSettingId
    ): ProjectProcedureSetting {
        $projectProcedure = ProjectProcedureSetting::query()
            ->withoutGlobalScopes()
            ->where('company_id', $project->company_id)
            ->where('project_id', $project->id)
            ->where('procedure_setting_id', $procedureSettingId)
            ->whereHas('procedureSetting', static function ($query) use ($project): void {
                $query->withoutGlobalScopes()
                    ->where('company_id', $project->company_id)
                    ->where('type', ProjectProcedureSetting::PROCEDURE_TYPE)
                    ->whereHas('workFlow', static function ($query) use ($project): void {
                        $query->withoutGlobalScopes()
                            ->where('company_id', $project->company_id)
                            ->where('project_id', $project->id)
                            ->where('type', ProjectProcedureSetting::PROCEDURE_TYPE);
                    });
            })
            ->first();

        if (!$projectProcedure) {
            throw ValidationException::withMessages([
                'procedure_setting_id' => 'Selected procedure must belong to the same project.',
            ]);
        }

        return $projectProcedure;
    }

    private function readerCompanyId(): string
    {
        $headerTenantId = request()->header('X-Tenant');
        if (is_string($headerTenantId) && $headerTenantId !== '') {
            return $headerTenantId;
        }

        $tenantId = tenant('id');
        if ($tenantId !== null) {
            return (string) $tenantId;
        }

        return '';
    }

    private function findRequestOrFail(string $requestId): AttachmentRequest
    {
        $request = $this->repository->getWithItems($requestId);

        if (! $request) {
            throw new \Exception('Attachment request not found');
        }

        return $request;
    }

    /**
     * Save approved attachment to ArchiveLibrary folder
     */
    private function saveAttachmentToFolder(AttachmentRequestItem $item): void
    {
        $this->archiveDeliveryService->deliverAttachmentRequestItem($item);
    }

    private function workflowApprovalMetadata(ProcessStep $step, ?Process $process = null): array
    {
        $process ??= $step->relationLoaded('process')
            ? $step->process
            : $step->process()->first();

        if ($process === null) {
            return [];
        }

        return AttachmentRequestHistory::workflowStepApprovalMetadata($process, $step);
    }

    private function workflowStepFromProcess(Process $process, ProcessStep $step): ProcessStep
    {
        $actedStep = $process->relationLoaded('steps')
            ? $process->steps->firstWhere('id', (string) $step->id)
            : null;

        if ($actedStep instanceof ProcessStep) {
            return $actedStep;
        }

        return $step->fresh() ?? $step;
    }

    /**
     * Get folder children for dropdown selection
     */
    public function getFolderChildren(?string $parentId = null, ?string $projectId = null): Collection
    {
        $query = Folder::query()->withoutTenancy();

        if ($parentId) {
            $query->where('parent_id', $parentId);
        } elseif ($projectId) {
            // Get project root folders
            $query->where('project_id', $projectId)->whereNull('parent_id');
        } else {
            $query->whereNull('parent_id');
        }

        return $query
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id', 'project_id']);
    }

    /**
     * Format file size to human readable format
     */
    private function formatFileSize(?int $bytes): string
    {
        if (!$bytes || $bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $size = $bytes;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * Broadcast notification to sender company users when request is responded
     */
    private function broadcastToSenderCompany(AttachmentRequest $request, string $action): void
    {
        // Get all users from sender company
        $senderCompanyUsers = User::where('company_id', $request->sender_company_id)
            ->whereNotNull('id')
            ->get();

        foreach ($senderCompanyUsers as $user) {
            event(new AttachmentRequestResponded($request, (string) $user->id, $action));
        }
    }

    /**
     * Replace media in attachment request item
     */
    public function replaceMedia(string $itemId, UploadedFile $newFile): AttachmentRequestItem
    {
        $item = AttachmentRequestItem::with('attachmentRequest')->findOrFail($itemId);
        $this->visibilityService->assertCompanyCanView($item->attachmentRequest, (string) tenant('id'));

        // Verify sender company (only sender can replace media)
//        if ($item->attachmentRequest->sender_company_id !== tenant('id')) {
//            throw new \Exception('Unauthorized to replace media for this item');
//        }

        // Verify item is pending or update_requested
        if (!in_array($item->status, ['pending', 'update_requested'])) {
            throw new \Exception('Can only replace media for pending or update requested items');
        }

        return DB::transaction(function () use ($item, $newFile) {
            // Clear existing media
            $item->clearMediaCollection('attachments');

            // Upload new file
            $this->fileUploadService->uploadFile(
                $item,
                $newFile,
                'attachment-requests/items',
                'attachments',
                'public'
            );

            // Update item file information
            $item->update([
                'file_name' => $newFile->getClientOriginalName(),
                'file_type' => $newFile->getClientMimeType(),
                'file_size' => $newFile->getSize(),
                'status' => 'pending', // Reset to pending after replacement
                'responded_by_user_id' => null,
                'responded_at' => null,
                'response_notes' => null,
            ]);

            // Log history
            AttachmentRequestHistory::log(
                requestId: $item->attachment_request_id,
                action: 'media_replaced',
                description: 'Media file replaced',
                userId: (string) Auth::id(),
                itemId: $item->id,
                metadata: [
                    'item_id' => $item->id,
                    'new_file_name' => $newFile->getClientOriginalName(),
                    'new_file_type' => $newFile->getClientMimeType(),
                    'new_file_size' => $newFile->getSize(),
                    'new_file_size_formatted' => $this->formatFileSize($newFile->getSize()),
                ]
            );

            // Update parent request status if needed
            $item->attachmentRequest->updateStatusBasedOnItems();

            return $item->fresh(['media', 'attachmentRequest']);
        });
    }
}
