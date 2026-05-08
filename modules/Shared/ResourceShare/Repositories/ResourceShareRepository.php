<?php

declare(strict_types=1);

namespace Modules\Shared\ResourceShare\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Shared\ResourceShare\Models\ResourceShare;
use Illuminate\Database\Eloquent\Collection;

class ResourceShareRepository extends BaseRepository
{
    public function __construct(ResourceShare $model)
    {
        parent::__construct($model);
    }

    /**
     * Get all shares for a specific resource
     */
    public function getSharesForResource(string $shareableType, string $shareableId): Collection
    {
        return $this->model
            ->where('shareable_type', $shareableType)
            ->where('shareable_id', $shareableId)
            ->with([
                'shareable' => function ($morphTo) {
                    $morphTo->constrain([
                        \Modules\Project\ProjectManagement\Models\ProjectManagement::class => function ($query) {
                            // Load projects from any company (bypass company_id filter)
                            $query->withoutGlobalScopes();
                        },
                    ]);
                },
                'sharedWithCompany',
                'respondedByUser'
            ])
            ->get();
    }

    /**
     * Get pending shares for a company
     */
    public function getPendingSharesForCompany(string $companyId): Collection
    {
        return $this->model
            ->where('shared_with_company_id', $companyId)
            ->where('status', 'pending')
            ->with([
                'shareable' => function ($morphTo) {
                    $morphTo->constrain([
                        \Modules\Project\ProjectManagement\Models\ProjectManagement::class => function ($query) {
                            // Load projects from any company (bypass company_id filter)
                            $query->withoutGlobalScopes();
                        },
                    ]);
                },
                'ownerCompany',
                'sharedByUser',
                'type',
                'relation',
                'role'
            ])
            ->get();
    }

    /**
     * Get accepted shares for a company
     */
    public function getAcceptedSharesForCompany(string $companyId): Collection
    {
        return $this->model
            ->where('shared_with_company_id', $companyId)
            ->where('status', 'accepted')
            ->with([
                'shareable' => function ($morphTo) {
                    $morphTo->constrain([
                        \Modules\Project\ProjectManagement\Models\ProjectManagement::class => function ($query) {
                            // Load projects from any company (bypass company_id filter)
                            $query->withoutGlobalScopes();
                        },
                    ]);
                },
                'ownerCompany'
            ])
            ->get();
    }

    /**
     * Create a new share
     */
    public function createShare(array $data): ResourceShare
    {
        return $this->model->create($data);
    }

    /**
     * Check if resource is already shared with company
     */
    public function isSharedWith(string $shareableType, string $shareableId, string $companyId): bool
    {
        return $this->model
            ->where('shareable_type', $shareableType)
            ->where('shareable_id', $shareableId)
            ->where('shared_with_company_id', $companyId)
            ->exists();
    }

    /**
     * Get share by ID
     */
    public function getShare(string $id): ?ResourceShare
    {
        return $this->model->find($id);
    }

    /**
     * Update share status
     */
    public function updateShareStatus(string $id, string $status, string $userId): bool
    {
        return $this->model
            ->where('id', $id)
            ->update([
                'status' => $status,
                'responded_by_user_id' => $userId,
                'responded_at' => now(),
            ]);
    }

    /**
     * Delete share
     */
    public function deleteShare(string $id): bool
    {
        return $this->model->where('id', $id)->delete();
    }

    /**
     * Get all shared resources of a specific type for a company
     */
    public function getSharedResourcesForCompany(string $companyId, string $shareableType): Collection
    {
        return $this->model
            ->where('shared_with_company_id', $companyId)
            ->where('shareable_type', $shareableType)
            ->where('status', 'accepted')
            ->with([
                'shareable' => function ($morphTo) {
                    $morphTo->constrain([
                        \Modules\Project\ProjectManagement\Models\ProjectManagement::class => function ($query) {
                            // Load projects from any company (bypass company_id filter)
                            $query->withoutGlobalScopes();
                        },
                    ]);
                }
            ])
            ->get();
    }
}
