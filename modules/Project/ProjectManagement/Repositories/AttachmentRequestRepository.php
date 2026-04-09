<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Project\ProjectManagement\Models\AttachmentRequest;
use Illuminate\Database\Eloquent\Collection;

class AttachmentRequestRepository extends BaseRepository
{
    public function __construct(AttachmentRequest $model)
    {
        parent::__construct($model);
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
            $request->items()->create($itemData);
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
