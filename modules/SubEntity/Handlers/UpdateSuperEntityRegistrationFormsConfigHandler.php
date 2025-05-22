<?php

declare(strict_types=1);

namespace Modules\SubEntity\Handlers;

use Modules\SubEntity\Repositories\SuperEntityRepository;
use Modules\SubEntity\Commands\UpdateSuperEntityRegistrationFormsConfigCommand;

class UpdateSuperEntityRegistrationFormsConfigHandler
{
    public function __construct(
        private SuperEntityRepository $repository,
    ) {
    }

    public function handle(UpdateSuperEntityRegistrationFormsConfigCommand $updateSuperEntityAttributesConfigCommand)
    {
        $this->repository->setConfigValue($updateSuperEntityAttributesConfigCommand->getId(),'registration_forms', $updateSuperEntityAttributesConfigCommand->toArray());
    }
}
