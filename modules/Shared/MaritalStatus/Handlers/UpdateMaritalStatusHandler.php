<?php

declare(strict_types=1);

namespace Modules\Shared\MaritalStatus\Handlers;

use Modules\Shared\MaritalStatus\Commands\UpdateMaritalStatusCommand;
use Modules\Shared\MaritalStatus\Repositories\MaritalStatusRepository;

class UpdateMaritalStatusHandler
{
    public function __construct(
        private MaritalStatusRepository $repository,
    ) {
    }

    public function handle(UpdateMaritalStatusCommand $updateMaritalStatusCommand)
    {
        $this->repository->updateMaritalStatus($updateMaritalStatusCommand->getId(), $updateMaritalStatusCommand->toArray());
    }
}
