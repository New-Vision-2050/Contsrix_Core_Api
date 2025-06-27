<?php

declare(strict_types=1);

namespace Modules\Subscription\Module\Handlers;

use Modules\Subscription\Module\Commands\UpdateModuleCommand;
use Modules\Subscription\Module\Repositories\ModuleRepository;

class UpdateModuleHandler
{
    public function __construct(
        private ModuleRepository $repository,
    ) {
    }

    public function handle(UpdateModuleCommand $updateModuleCommand)
    {
        $this->repository->updateModule($updateModuleCommand->getId(), $updateModuleCommand->toArray());
    }
}
