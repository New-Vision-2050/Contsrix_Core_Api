<?php

declare(strict_types=1);

namespace Modules\Shared\TypeWorkingHour\Handlers;

use Modules\Shared\TypeWorkingHour\Commands\UpdateTypeWorkingHourCommand;
use Modules\Shared\TypeWorkingHour\Repositories\TypeWorkingHourRepository;

class UpdateTypeWorkingHourHandler
{
    public function __construct(
        private TypeWorkingHourRepository $repository,
    ) {
    }

    public function handle(UpdateTypeWorkingHourCommand $updateTypeWorkingHourCommand)
    {
        $this->repository->updateTypeWorkingHour($updateTypeWorkingHourCommand->getId(), $updateTypeWorkingHourCommand->toArray());
    }
}
