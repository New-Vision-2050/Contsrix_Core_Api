<?php

declare(strict_types=1);

namespace Modules\SubEntity\Handlers;

use Modules\SubEntity\Repositories\SuperEntityRepository;
use Modules\SubEntity\Commands\UpdateSuperEntityRegistrationCommand;

class UpdateSuperEntityRegistrationConfigHandler
{
    public function __construct(
        private SuperEntityRepository $repository,
    ) {
    }

    public function handle(UpdateSuperEntityRegistrationCommand $updateSuperEntityRegistrationConfigCommand)
    {
        $this->repository->setMultipleConfigValues($updateSuperEntityRegistrationConfigCommand->getId(),$updateSuperEntityRegistrationConfigCommand->toArray());
    }
}
