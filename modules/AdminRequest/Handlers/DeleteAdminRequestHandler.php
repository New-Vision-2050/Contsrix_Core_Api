<?php

declare(strict_types=1);

namespace Modules\AdminRequest\Handlers;

use Modules\AdminRequest\Repositories\AdminRequestRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteAdminRequestHandler
{
    public function __construct(
        private AdminRequestRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteAdminRequest($id);
    }
}
