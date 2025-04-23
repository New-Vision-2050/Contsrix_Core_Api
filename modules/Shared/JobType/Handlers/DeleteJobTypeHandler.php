<?php

declare(strict_types=1);

namespace Modules\Shared\JobType\Handlers;

use Modules\Shared\JobType\Repositories\JobTypeRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteJobTypeHandler
{
    public function __construct(
        private JobTypeRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteJobType($id);
    }
}
