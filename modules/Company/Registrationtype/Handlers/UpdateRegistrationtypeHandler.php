<?php

declare(strict_types=1);

namespace Modules\Company\RegistrationType\Handlers;

use Modules\Company\RegistrationType\Commands\UpdateRegistrationTypeCommand;
use Modules\Company\RegistrationType\Repositories\RegistrationTypeRepository;

class UpdateRegistrationTypeHandler
{
    public function __construct(
        private RegistrationTypeRepository $repository,
    ) {
    }

    public function handle(UpdateRegistrationTypeCommand $updateRegistrationTypeCommand)
    {
        $this->repository->updateRegistrationType($updateRegistrationTypeCommand->getId(), $updateRegistrationTypeCommand->toArray());
    }
}
