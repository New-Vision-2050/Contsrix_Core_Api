<?php

declare(strict_types=1);

namespace Modules\UserInfo\Contactinfo\Handlers;

use Modules\UserInfo\Contactinfo\Commands\UpdateContactinfoCommand;
use Modules\UserInfo\Contactinfo\Repositories\ContactinfoRepository;

class UpdateContactinfoHandler
{
    public function __construct(
        private ContactinfoRepository $repository,
    ) {
    }

    public function handle(UpdateContactinfoCommand $updateContactinfoCommand)
    {
     return   $this->repository->updateContactinfo($updateContactinfoCommand->companyUserId, $updateContactinfoCommand->toArray());
    }
}
