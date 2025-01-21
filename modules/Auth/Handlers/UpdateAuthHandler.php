<?php

declare(strict_types=1);

namespace Modules\Auth\Handlers;

use Modules\Auth\Commands\UpdateAuthCommand;
use Modules\Auth\Repositories\AuthRepository;

class UpdateAuthHandler
{
    public function __construct(
        private AuthRepository $repository,
    ) {
    }

    public function handle(UpdateAuthCommand $updateAuthCommand)
    {
        $this->repository->updateAuth($updateAuthCommand->getId(), $updateAuthCommand->toArray());
    }
}
