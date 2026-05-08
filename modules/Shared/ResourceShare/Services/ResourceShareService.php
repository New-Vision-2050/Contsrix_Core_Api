<?php

declare(strict_types=1);

namespace Modules\Shared\ResourceShare\Services;

use Modules\Shared\ResourceShare\Repositories\ResourceShareRepository;
use Modules\Shared\ResourceShare\Models\ResourceShare;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\Shared\ResourceShare\Events\ResourceShared;
use Modules\Shared\ResourceShare\Events\ResourceShareResponded;
use Modules\User\Models\User;

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
        ?string $notes = null,
        ?int $typeId = null,
        ?int $relationId = null,
        ?int $roleId = null
    ): ResourceShare {
        // Check if already shared
        if ($this->repository->isSharedWith($shareableType, $shareableId, $sharedWithCompanyId)) {
            throw new \Exception('Resource is already shared with this company');
        }

        $share = $this->repository->createShare([
            'shareable_type' => $shareableType,
            'shareable_id' => $shareableId,
            'owner_company_id' => $ownerCompanyId,
            'shared_with_company_id' => $sharedWithCompanyId,
            'type_id' => $typeId,
            'relation_id' => $relationId,
            'role_id' => $roleId,
            'status' => 'pending',
            'schema_ids' => $schemaIds,
            'shared_by_user_id' => Auth::id(),
            'notes' => $notes,
        ]);

        // Broadcast notification to shared company users
        $this->broadcastToSharedCompany($share);

        return $share;
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

        $result = $share->accept((string) Auth::id());

        // Reload relationships
        $share = $share->fresh(['ownerCompany', 'sharedWithCompany', 'respondedByUser', 'shareable']);

        // Broadcast notification to owner company users
        $this->broadcastToOwnerCompany($share, 'accepted');

        return $result;
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

        $result = $share->reject((string) Auth::id());

        // Reload relationships
        $share = $share->fresh(['ownerCompany', 'sharedWithCompany', 'respondedByUser', 'shareable']);

        // Broadcast notification to owner company users
        $this->broadcastToOwnerCompany($share, 'rejected');

        return $result;
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

    /**
     * Broadcast notification to shared company when resource is shared
     */
    private function broadcastToSharedCompany(ResourceShare $share): void
    {
        // Load all relationships needed for broadcasting
        $share->load(['ownerCompany', 'sharedWithCompany', 'sharedByUser', 'shareable']);

        \Log::info('Broadcasting ResourceShared to company channel: company.' . $share->shared_with_company_id);

        // Broadcast a single event to the company channel
        event(new ResourceShared($share));
    }

    /**
     * Broadcast notification to owner company when share is responded
     */
    private function broadcastToOwnerCompany(ResourceShare $share, string $action): void
    {
        // Load all relationships needed for broadcasting
        $share->load(['ownerCompany', 'sharedWithCompany', 'sharedByUser', 'shareable', 'respondedByUser']);

        \Log::info('Broadcasting ResourceShareResponded to company channel: company.' . $share->owner_company_id);

        // Broadcast a single event to the company channel
        event(new ResourceShareResponded($share, $action));
    }
}
