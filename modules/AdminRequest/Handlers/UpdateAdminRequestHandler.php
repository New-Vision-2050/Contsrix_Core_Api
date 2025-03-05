<?php

declare(strict_types=1);

namespace Modules\AdminRequest\Handlers;

use Modules\AdminRequest\Commands\UpdateAdminRequestCommand;
use Modules\AdminRequest\Repositories\AdminRequestRepository;

class UpdateAdminRequestHandler
{
    public function __construct(
        private AdminRequestRepository $repository,
    ) {
    }

    public function handle(UpdateAdminRequestCommand $updateAdminRequestCommand)
    {
        $this->repository->updateAdminRequest($updateAdminRequestCommand->getId(), $updateAdminRequestCommand->toArray());
    }
}
