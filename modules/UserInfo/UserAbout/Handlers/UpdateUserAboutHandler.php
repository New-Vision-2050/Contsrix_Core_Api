<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserAbout\Handlers;

use Modules\UserInfo\UserAbout\Commands\UpdateUserAboutCommand;
use Modules\UserInfo\UserAbout\Repositories\UserAboutRepository;

class UpdateUserAboutHandler
{
    public function __construct(
        private UserAboutRepository $repository,
    ) {
    }

    public function handle(UpdateUserAboutCommand $updateUserAboutCommand)
    {
        $this->repository->updateUserAbout($updateUserAboutCommand->getId(), $updateUserAboutCommand->toArray());
    }
}
