<?php

declare(strict_types=1);

namespace Modules\Unit\Handlers;

use Modules\Unit\Commands\UpdateUnitCommand;
use Modules\Unit\Repositories\UnitRepository;

class UpdateUnitHandler
{
    public function __construct(
        private UnitRepository $repository,
    ) {
    }

    public function handle(UpdateUnitCommand $updateUnitCommand)
    {
        $this->repository->updateUnit($updateUnitCommand->getId(), $updateUnitCommand->toArray());
    }
}
