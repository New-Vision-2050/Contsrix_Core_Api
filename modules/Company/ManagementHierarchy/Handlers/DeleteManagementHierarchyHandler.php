<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Handlers;

use Exception;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteManagementHierarchyHandler
{
    public function __construct(
        private ManagementHierarchyRepository $repository,
    ) {
    }

    /**
     * Handle the deletion of a management hierarchy
     *
     * @param int $id The ID of the management hierarchy to delete
     * @throws Exception If the management hierarchy has children and cannot be deleted
     */
    public function handle(int $id)
    {
        // Check if management has children before deleting
        if ($this->repository->hasChildren($id)) {
            throw new Exception('Cannot delete management hierarchy that has children.', 422);
        }

        $this->repository->deleteManagementHierarchy($id);
    }
}
