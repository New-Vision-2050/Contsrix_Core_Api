<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Handlers;

use Modules\Project\ProjectManagement\Repositories\ProjectManagementRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteProjectManagementHandler
{
    public function __construct(
        private ProjectManagementRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteProjectManagement($id);
    }
}
