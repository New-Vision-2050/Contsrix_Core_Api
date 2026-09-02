<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Project\ProjectManagement\Models\AttachmentRequest;
use Modules\Project\ProjectManagement\Models\ProjectRequirementSubmission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Shared\Media\Services\FileUploadService;
use Modules\Project\ProjectManagement\Services\AttachmentRequestVisibilityService;
use Modules\User\Models\User;

class AttachmentRequestRepository extends BaseRepository
{
    public function __construct(
        AttachmentRequest $model,
        private FileUploadService $fileUploadService,
        private AttachmentRequestVisibilityService $visibilityService
    ) {
        parent::__construct($model);
    }

    /**
     * Get all requests (incoming and outgoing) for a company with optional filters
     *
     * Accepted filters:
     *   project_id  – filter by project UUID
     *   procedure_setting_id – filter by procedure setting UUID
     *   receiver_company_ids – receiver company UUIDs configured for the procedure
     *   type        – filter by status  (pending|approved|declined|semi-approved)
     *   direction   – 'outgoing' (sender) | 'incoming' (workflow-driven, no stored receiver)
     *   name        – partial search on serial_number
     *   per_page    – items per page (default 15)
     *   page        – page number    (default 1)
     */
    public function getAllRequests(string $companyId, array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        return $this->buildAllRequestsQuery($companyId, $filters)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Same filtering as getAllRequests(), but returns the full collection so the
     * service can merge attachment requests with requirement submissions before
     * paginating the unified inbox.
     */
    public function getAllRequestsCollection(string $companyId, array $filters = []): Collection
    {
        return $this->buildAllRequestsQuery($companyId, $filters)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function buildAllRequestsQuery(string $companyId, array $filters = [])
    {
        $query = $this->model->with([
            'project',
            'procedureSetting',
            'projectProcedureSetting.attachmentType:id,name,parent_id,project_id,company_id',
            'projectProcedureSetting.attachmentSubType:id,name,parent_id,project_id,company_id',
            'projectProcedureSetting.attachmentSubSubType:id,name,parent_id,project_id,company_id',
            'senderCompany',
            'createdByUser',
            'respondedByUser',
            'items.respondedByUser',
            'history.user',
            'attachmentRequestProcess.steps' => fn ($query) => $this->orderProcessSteps($query),
        ]);

        $direction = $filters['direction'] ?? null;

        if ($direction === 'outgoing') {
            $query->where('sender_company_id', $companyId);
        } elseif ($direction === 'incoming') {
            $this->applyIncomingScope($query, $companyId);
            $query->where('sender_company_id', '!=', $companyId);
        } else {
            // Default: both outgoing (sent by me) and incoming (visible to my company).
            $this->visibilityService->applyVisibleToCompany($query, $companyId);
        }

        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (!empty($filters['procedure_setting_id'])) {
            $query->where('procedure_setting_id', $filters['procedure_setting_id']);
        }

        $this->applyProcedureReceiverCompanyFilter($query, $filters['receiver_company_ids'] ?? []);

        if (!empty($filters['contractual_engagement_key'])) {
            $query->whereHas('project.contractualEngagement', function ($q) use ($filters) {
                $q->where('code', $filters['contractual_engagement_key']);
            });
        }

        if (!empty($filters['type'])) {
            $query->where('status', $filters['type']);
        }

        if (!empty($filters['name'])) {
            $query->where('serial_number', 'like', '%' . $filters['name'] . '%');
        }

        return $query;
    }

    /**
     * Requirement submissions that belong in this company's unified inbox.
     * Mirrors the attachment-request directions:
     *   outgoing  – my company uploaded the submission (process metadata),
     *   incoming  – my company is a workflow action-taker on the submission,
     *   default   – both.
     */
    public function getRequirementSubmissionsInbox(
        string $companyId,
        array $filters = [],
        ?string $direction = null,
    ): Collection {
        $companyUserIds = $this->companyUserIds($companyId);

        $query = ProjectRequirementSubmission::query()
            ->withoutGlobalScopes()
            ->with([
                'project',
                'requirement.procedureSetting',
                'media',
                'projectRequirementSubmissionProcess.steps' => fn ($query) => $this->orderProcessSteps($query),
            ]);

        if ($direction === 'outgoing') {
            $this->applyUploaderScope($query, $companyId);
        } elseif ($direction === 'incoming') {
            $this->applyActionTakerScope($query, $companyUserIds);
            $this->applyNotUploaderScope($query, $companyId);
        } else {
            $query->where(function ($q) use ($companyId, $companyUserIds): void {
                $q->where(function ($q) use ($companyId): void {
                    $this->applyUploaderScope($q, $companyId);
                })->orWhere(function ($q) use ($companyUserIds): void {
                    $this->applyActionTakerScope($q, $companyUserIds);
                });
            });
        }

        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (!empty($filters['procedure_setting_id'])) {
            $query->whereHas('requirement', function ($query) use ($filters): void {
                $query->where('procedure_setting_id', $filters['procedure_setting_id']);
            });
        }

        $this->applySubmissionProcedureReceiverCompanyFilter(
            $query,
            $filters['receiver_company_ids'] ?? []
        );

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Limit attachment requests to procedures configured for any supplied
     * receiver company. Receiver companies live on the project procedure,
     * not on the attachment request itself.
     *
     * @param array<int, string> $receiverCompanyIds
     */
    private function applyProcedureReceiverCompanyFilter($query, array $receiverCompanyIds): void
    {
        if ($receiverCompanyIds === []) {
            return;
        }

        $query->whereExists(function ($receiverQuery) use ($receiverCompanyIds): void {
            $receiverQuery->selectRaw('1')
                ->from('project_procedure_settings as receiver_procedure_settings')
                ->join(
                    'project_procedure_setting_receiver_companies as procedure_receiver_companies',
                    'procedure_receiver_companies.project_procedure_setting_id',
                    '=',
                    'receiver_procedure_settings.id'
                )
                ->whereColumn('receiver_procedure_settings.project_id', 'attachment_requests.project_id')
                ->whereColumn(
                    'receiver_procedure_settings.procedure_setting_id',
                    'attachment_requests.procedure_setting_id'
                )
                ->whereExists(static function ($projectQuery): void {
                    $projectQuery->selectRaw('1')
                        ->from('projects')
                        ->whereColumn('projects.id', 'attachment_requests.project_id')
                        ->whereColumn('projects.company_id', 'receiver_procedure_settings.company_id');
                })
                ->whereIn('procedure_receiver_companies.company_id', $receiverCompanyIds);
        });
    }

    /**
     * Apply the same project-procedure receiver-company filter to requirement
     * submissions in the unified inbox.
     *
     * @param array<int, string> $receiverCompanyIds
     */
    private function applySubmissionProcedureReceiverCompanyFilter($query, array $receiverCompanyIds): void
    {
        if ($receiverCompanyIds === []) {
            return;
        }

        $query->whereExists(function ($receiverQuery) use ($receiverCompanyIds): void {
            $receiverQuery->selectRaw('1')
                ->from('project_requirements as submission_requirements')
                ->join(
                    'project_procedure_settings as receiver_procedure_settings',
                    function ($join): void {
                        $join->on(
                            'receiver_procedure_settings.project_id',
                            '=',
                            'submission_requirements.project_id'
                        )->on(
                            'receiver_procedure_settings.procedure_setting_id',
                            '=',
                            'submission_requirements.procedure_setting_id'
                        );
                    }
                )
                ->join(
                    'project_procedure_setting_receiver_companies as procedure_receiver_companies',
                    'procedure_receiver_companies.project_procedure_setting_id',
                    '=',
                    'receiver_procedure_settings.id'
                )
                ->whereColumn(
                    'submission_requirements.id',
                    'project_requirement_submissions.project_requirement_id'
                )
                ->whereExists(static function ($projectQuery): void {
                    $projectQuery->selectRaw('1')
                        ->from('projects')
                        ->whereColumn('projects.id', 'project_requirement_submissions.project_id')
                        ->whereColumn('projects.company_id', 'receiver_procedure_settings.company_id');
                })
                ->whereIn('procedure_receiver_companies.company_id', $receiverCompanyIds);
        });
    }

    /**
     * Get outgoing requests for a company
     */
    public function getOutgoingRequests(string $companyId, ?string $projectId = null): Collection
    {
        $query = $this->model
            ->where('sender_company_id', $companyId)
            ->with([
                'project',
                'procedureSetting',
                'projectProcedureSetting.attachmentType:id,name,parent_id,project_id,company_id',
                'projectProcedureSetting.attachmentSubType:id,name,parent_id,project_id,company_id',
                'projectProcedureSetting.attachmentSubSubType:id,name,parent_id,project_id,company_id',
                'createdByUser',
                'respondedByUser',
                'items.respondedByUser',
                'history.user',
                'attachmentRequestProcess.steps' => fn ($query) => $this->orderProcessSteps($query),
            ]);

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get incoming requests for a company
     */
    public function getIncomingRequests(string $companyId, ?string $projectId = null): Collection
    {
        $query = $this->model->newQuery()->with([
            'project',
            'procedureSetting',
            'projectProcedureSetting.attachmentType:id,name,parent_id,project_id,company_id',
            'projectProcedureSetting.attachmentSubType:id,name,parent_id,project_id,company_id',
            'projectProcedureSetting.attachmentSubSubType:id,name,parent_id,project_id,company_id',
            'senderCompany',
            'createdByUser',
            'respondedByUser',
            'items.respondedByUser',
            'history.user',
            'attachmentRequestProcess.steps' => fn ($query) => $this->orderProcessSteps($query),
        ]);

        $this->applyIncomingScope($query, $companyId);

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get requests by project
     */
    public function getByProject(string $projectId): Collection
    {
        return $this->model
            ->where('project_id', $projectId)
            ->with([
                'senderCompany',
                'procedureSetting',
                'projectProcedureSetting.attachmentType:id,name,parent_id,project_id,company_id',
                'projectProcedureSetting.attachmentSubType:id,name,parent_id,project_id,company_id',
                'projectProcedureSetting.attachmentSubSubType:id,name,parent_id,project_id,company_id',
                'createdByUser',
                'respondedByUser',
                'items.respondedByUser',
                'history.user',
                'attachmentRequestProcess.steps' => fn ($query) => $this->orderProcessSteps($query),
            ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get request with items
     */
    public function getWithItems(string $requestId): ?AttachmentRequest
    {
        return $this->model
            ->with([
                'project',
                'procedureSetting',
                'projectProcedureSetting.attachmentType:id,name,parent_id,project_id,company_id',
                'projectProcedureSetting.attachmentSubType:id,name,parent_id,project_id,company_id',
                'projectProcedureSetting.attachmentSubSubType:id,name,parent_id,project_id,company_id',
                'senderCompany',
                'createdByUser',
                'respondedByUser',
                'items.respondedByUser',
                'history.user',
                'attachmentRequestProcess.steps' => fn ($query) => $this->orderProcessSteps($query),
            ])
            ->find($requestId);
    }

    /**
     * Create request with items
     */
    public function createWithItems(array $requestData, array $items): AttachmentRequest
    {
        $request = $this->create($requestData);

        foreach ($items as $itemData) {
            $uploadedFile = $itemData['uploaded_file'] ?? null;
            unset($itemData['uploaded_file']);

            $item = $request->items()->create($itemData);

            if ($uploadedFile) {
                $this->fileUploadService->uploadFile(
                    $item,
                    $uploadedFile,
                    'attachment-requests',
                    'attachments',
                    'public'
                );

                $media = $item->getFirstMedia('attachments');
                if ($media) {
                    $item->update(['file_path' => $media->getPath()]);
                }
            }
        }

        return $request->load([
            'items',
            'procedureSetting',
            'attachmentRequestProcess.steps' => fn ($query) => $this->orderProcessSteps($query),
        ]);
    }

    /**
     * Get pending requests for a company
     */
    public function getPendingIncoming(string $companyId, ?string $projectId = null): Collection
    {
        $query = $this->model
            ->newQuery()
            ->whereIn('status', ['pending', 'semi-approved'])
            ->with([
                'project',
                'procedureSetting',
                'projectProcedureSetting.attachmentType:id,name,parent_id,project_id,company_id',
                'projectProcedureSetting.attachmentSubType:id,name,parent_id,project_id,company_id',
                'projectProcedureSetting.attachmentSubSubType:id,name,parent_id,project_id,company_id',
                'senderCompany',
                'createdByUser',
                'items.respondedByUser',
                'history.user',
                'attachmentRequestProcess.steps' => fn ($query) => $this->orderProcessSteps($query),
            ]);

        $this->applyIncomingScope($query, $companyId);

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function companyCanView(string $requestId, string $companyId): bool
    {
        $query = $this->model->newQuery()->whereKey($requestId);
        $this->visibilityService->applyVisibleToCompany($query, $companyId);

        return $query->exists();
    }

    /**
     * Restrict a query to attachment requests that are "incoming" for a company:
     * requests whose selected project procedure is visible to that receiver
     * company. Empty receiver-company lists retain legacy unrestricted sharing.
     */
    private function applyIncomingScope($query, string $companyId): void
    {
        $this->visibilityService->applyReceiverCompanyVisibility($query, $companyId);
    }

    /**
     * @return list<string>
     */
    private function companyUserIds(string $companyId): array
    {
        return User::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->all();
    }

    /**
     * Restrict a query (whose model exposes a `processes` relation pre-scoped to a
     * single processable_type) to rows where one of the given users is a workflow
     * action-taker (assigned_user_id or authorized_user_ids).
     *
     * @param  list<string>  $companyUserIds
     */
    private function applyActionTakerScope($query, array $companyUserIds): void
    {
        if ($companyUserIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereHas('processes', function ($q) use ($companyUserIds): void {
            $q->whereHas('steps', function ($q) use ($companyUserIds): void {
                $q->where(function ($q) use ($companyUserIds): void {
                    $q->whereIn('assigned_user_id', $companyUserIds);
                    foreach ($companyUserIds as $uid) {
                        $q->orWhereJsonContains('authorized_user_ids', $uid);
                    }
                });
            });
        });
    }

    /**
     * Restrict a query (whose model exposes a `processes` relation) to rows the
     * given company uploaded, identified by the workflow process metadata.
     */
    private function applyUploaderScope($query, string $companyId): void
    {
        $query->whereHas('processes', function ($q) use ($companyId): void {
            $q->where('metadata->uploader_company_id', $companyId);
        });
    }

    /**
     * Exclude submissions uploaded by the current company from its incoming
     * list, even when one of its users is also a workflow action-taker.
     */
    private function applyNotUploaderScope($query, string $companyId): void
    {
        $query->whereDoesntHave('processes', function ($q) use ($companyId): void {
            $q->where('metadata->uploader_company_id', $companyId);
        });
    }

    /**
     * Generate unique serial number
     */
    public function generateSerialNumber(): string
    {
        $prefix = 'ATR';
        $date = now()->format('Ymd');

        $lastRequest = $this->model
            ->where('serial_number', 'like', $prefix . '-' . $date . '-%')
            ->orderBy('serial_number', 'desc')
            ->first();

        if ($lastRequest) {
            $lastNumber = (int) substr($lastRequest->serial_number, -4);
            $newNumber = str_pad((string)($lastNumber + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . '-' . $date . '-' . $newNumber;
    }

    private function orderProcessSteps($query)
    {
        return $query->orderByRaw('(template_step_order IS NULL) ASC')
            ->orderBy('template_step_order')
            ->orderBy('created_at');
    }
}
