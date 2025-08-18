<?php

declare(strict_types=1);

namespace Modules\Test\Handlers;

use Modules\Test\Commands\UpdateTestCommand;
use Modules\Test\Repositories\TestRepository;

class UpdateTestHandler
{
    public function __construct(
        private TestRepository $repository,
    ) {
    }

    public function handle(UpdateTestCommand $updateTestCommand)
    {
        $this->repository->updateTest($updateTestCommand->getId(), $updateTestCommand->toArray());
    }
}
