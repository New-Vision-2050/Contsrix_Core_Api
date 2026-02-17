<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Handlers;

use Modules\Project\ProjectType\Repositories\ProjectTypeRepository;

class DeleteProjectTypeHandler
{
    public function __construct(
        private ProjectTypeRepository $repository,
    ) {
    }

    public function handle(int $id)
    {
        $this->repository->deleteProjectType($id);
    }
}
