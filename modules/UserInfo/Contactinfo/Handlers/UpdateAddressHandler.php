<?php

declare(strict_types=1);

namespace Modules\UserInfo\Contactinfo\Handlers;

use Modules\UserInfo\Contactinfo\Commands\UpdateAddressCommand;
use Modules\UserInfo\Contactinfo\Commands\UpdateContactinfoCommand;
use Modules\UserInfo\Contactinfo\Repositories\ContactinfoRepository;

class UpdateAddressHandler
{
    public function __construct(
        private ContactinfoRepository $repository,
    ) {
    }

    public function handle(UpdateAddressCommand $updateAddressCommand)
    {
     return   $this->repository->updateContactinfo($updateAddressCommand->getId(), $updateAddressCommand->toArray());
    }
}
