<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Project\ProjectManagement\Models\AttachmentRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Shared\Media\Services\FileUploadService;

class AttachmentRequestRepository extends BaseRepository
{
    public function __construct(
        AttachmentRequest $model,
        private FileUploadService $fileUploadService
    ) {
        parent::__construct($model);
    }

    /**
     * Get all requests (incoming and outgoing) for a company with optional filters
     *
     * Accepted filters:
     *   project_id  – filter by project UUID
     *   type        – filter by status  (pending|approved|declined|semi-approved)
     *   direction   – 'outgoing' (sender) | 'incoming' (receiver)
     *   receiver_id – filter by receiver_company_id
     *   name        – partial search on serial_number
     *   per_page    – items per page (default 15)
     *   page        – page number    (default 1)
     */
    public function getAllRequests(string $companyId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->with([
            'project',
            'senderCompany',
            'receiverCompany',
            'createdByUser',
            'respondedByUser',
            'items.respondedByUser',
        ]);

        $direction = $filters['direction'] ?? null;

        if ($direction === 'outgoing') {
            $query->where('sender_company_id', $companyId);
        } elseif ($direction === 'incoming') {
            $query->where('receiver_company_id', $companyId);
        } else {
            $query->where(function ($q) use ($companyId) {
                $q->where('sender_company_id', $companyId)
                  ->orWhere('receiver_company_id', $companyId);
            });
        }

        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('status', $filters['type']);
        }

        if (!empty($filters['receiver_id'])) {
            $query->where('receiver_company_id', $filters['receiver_id']);
        }

        if (!empty($filters['name'])) {
            $query->where('serial_number', 'like', '%' . $filters['name'] . '%');
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
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
                'receiverCompany',
                'createdByUser',
                'respondedByUser',
                'items.respondedByUser'
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
        $query = $this->model
            ->where('receiver_company_id', $companyId)
            ->with([
                'project',
                'senderCompany',
                'createdByUser',
                'respondedByUser',
                'items.respondedByUser'
            ]);

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
                'receiverCompany',
                'createdByUser',
                'respondedByUser',
                'items.respondedByUser'
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
                'senderCompany',
                'receiverCompany',
                'createdByUser',
                'respondedByUser',
                'items.respondedByUser',
                'history.user'
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

        return $request->load('items');
    }

    /**
     * Get pending requests for a company
     */
    public function getPendingIncoming(string $companyId, ?string $projectId = null): Collection
    {
        $query = $this->model
            ->where('receiver_company_id', $companyId)
            ->whereIn('status', ['pending', 'semi-approved'])
            ->with([
                'project',
                'senderCompany',
                'createdByUser',
                'items.respondedByUser'
            ]);

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return $query->orderBy('created_at', 'desc')->get();
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
}
