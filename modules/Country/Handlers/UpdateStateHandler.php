<?php

declare(strict_types=1);

namespace Modules\Country\Handlers;

use Modules\Country\Commands\UpdateStateCommand;
use Modules\Country\Repositories\StateRepository;

class UpdateStateHandler
{
    public function __construct(
        private StateRepository $repository,
    ) {
    }

    public function handle(UpdateStateCommand $updateStateCommand)
    {
        $this->repository->updateState($updateStateCommand->getId(), $updateStateCommand->toArray());
    }
}
