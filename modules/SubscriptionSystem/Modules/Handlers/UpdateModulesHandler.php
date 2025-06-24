<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Modules\Handlers;

use Modules\SubscriptionSystem\Modules\Commands\UpdateModulesCommand;
use Modules\SubscriptionSystem\Modules\Repositories\ModulesRepository;

class UpdateModulesHandler
{
    public function __construct(
        private ModulesRepository $repository,
    ) {
    }

    public function handle(UpdateModulesCommand $updateModulesCommand)
    {
        $this->repository->updateModules($updateModulesCommand->getId(), $updateModulesCommand->toArray());
    }
}
