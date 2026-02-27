<?php

declare(strict_types=1);

namespace Modules\Project\TermServices\Handlers;

use Modules\Project\TermServices\Repositories\TermServicesRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteTermServicesHandler
{
    public function __construct(
        private TermServicesRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteTermServices($id);
    }
}
