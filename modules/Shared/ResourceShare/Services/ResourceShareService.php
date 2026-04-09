<?php

declare(strict_types=1);

namespace Modules\Shared\ResourceShare\Services;

use Modules\Shared\ResourceShare\Repositories\ResourceShareRepository;
use Modules\Shared\ResourceShare\Models\ResourceShare;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ResourceShareService
{
    public function __construct(
        private ResourceShareRepository $repository
    ) {
    }

    /**
     * Share a resource with a company
     */
    public function shareResource(
        string $shareableType,
        string $shareableId,
        string $ownerCompanyId,
        string $sharedWithCompanyId,
        ?array $schemaIds = null,
        ?string $notes = null
    ): ResourceShare {
        // Check if already shared
        if ($this->repository->isSharedWith($shareableType, $shareableId, $sharedWithCompanyId)) {
            throw new \Exception('Resource is already shared with this company');
        }

        return $this->repository->createShare([
            'shareable_type' => $shareableType,
            'shareable_id' => $shareableId,
            'owner_company_id' => $ownerCompanyId,
            'shared_with_company_id' => $sharedWithCompanyId,
            'status' => 'pending',
            'schema_ids' => $schemaIds,
            'shared_by_user_id' => Auth::id(),
            'notes' => $notes,
        ]);
    }

    /**
     * Accept a share invitation
     */
    public function acceptShare(string $shareId): bool
    {
        $share = $this->repository->getShare($shareId);
        
        if (!$share) {
            throw new \Exception('Share not found');
        }

        if ($share->shared_with_company_id !== tenant('id')) {
            throw new \Exception('Unauthorized to accept this share');
        }

        if (!$share->isPending()) {
            throw new \Exception('Share is not pending');
        }

        return $share->accept((string) Auth::id());
    }

    /**
     * Reject a share invitation
     */
    public function rejectShare(string $shareId): bool
    {
        $share = $this->repository->getShare($shareId);
        
        if (!$share) {
            throw new \Exception('Share not found');
        }

        if ($share->shared_with_company_id !== tenant('id')) {
            throw new \Exception('Unauthorized to reject this share');
        }

        if (!$share->isPending()) {
            throw new \Exception('Share is not pending');
        }

        return $share->reject((string) Auth::id());
    }

    /**
     * Get pending invitations for current company
     */
    public function getPendingInvitations(): Collection
    {
        return $this->repository->getPendingSharesForCompany(tenant('id'));
    }

    /**
     * Get accepted shares for current company
     */
    public function getAcceptedShares(): Collection
    {
        return $this->repository->getAcceptedSharesForCompany(tenant('id'));
    }

    /**
     * Get all shares for a specific resource
     */
    public function getSharesForResource(string $shareableType, string $shareableId): Collection
    {
        return $this->repository->getSharesForResource($shareableType, $shareableId);
    }

    /**
     * Remove a share
     */
    public function removeShare(string $shareId): bool
    {
        $share = $this->repository->getShare($shareId);
        
        if (!$share) {
            throw new \Exception('Share not found');
        }

        // Only owner company can remove share
        if ($share->owner_company_id !== tenant('id')) {
            throw new \Exception('Unauthorized to remove this share');
        }

        return $this->repository->deleteShare($shareId);
    }

    /**
     * Get shared resources of a specific type for current company
     */
    public function getSharedResources(string $shareableType): Collection
    {
        return $this->repository->getSharedResourcesForCompany(tenant('id'), $shareableType);
    }

    /**
     * Update share schemas
     */
    public function updateShareSchemas(string $shareId, array $schemaIds): bool
    {
        $share = $this->repository->getShare($shareId);
        
        if (!$share) {
            throw new \Exception('Share not found');
        }

        if ($share->owner_company_id !== tenant('id')) {
            throw new \Exception('Unauthorized to update this share');
        }

        return $share->update(['schema_ids' => $schemaIds]);
    }
}
