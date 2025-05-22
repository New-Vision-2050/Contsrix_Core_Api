<?php

declare(strict_types=1);

namespace Modules\SubEntity\Handlers;

use Modules\SubEntity\Repositories\SuperEntityRepository;
use Modules\SubEntity\Commands\UpdateSuperEntityRegistrableConfigCommand;
use Modules\SubEntity\Commands\UpdateSuperEntityRegistrationFormsConfigCommand;

class UpdateSuperEntityRegistrableConfigHandler
{
    public function __construct(
        private SuperEntityRepository $repository,
    ) {
    }

    public function handle(UpdateSuperEntityRegistrableConfigCommand $updateSuperEntityRegistrableConfigCommand)
    {
        $this->repository->setConfigValue($updateSuperEntityRegistrableConfigCommand->getId(),'is_registrable', $updateSuperEntityRegistrableConfigCommand->getIsRegistrable());
    }
}
