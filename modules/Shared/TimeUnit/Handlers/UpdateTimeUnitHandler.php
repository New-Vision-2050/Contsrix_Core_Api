<?php

declare(strict_types=1);

namespace Modules\Shared\TimeUnit\Handlers;

use Modules\Shared\TimeUnit\Commands\UpdateTimeUnitCommand;
use Modules\Shared\TimeUnit\Repositories\TimeUnitRepository;

class UpdateTimeUnitHandler
{
    public function __construct(
        private TimeUnitRepository $repository,
    ) {
    }

    public function handle(UpdateTimeUnitCommand $updateTimeUnitCommand)
    {
        $this->repository->updateTimeUnit($updateTimeUnitCommand->getId(), $updateTimeUnitCommand->toArray());
    }
}
