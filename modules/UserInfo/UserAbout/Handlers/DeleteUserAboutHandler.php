<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserAbout\Handlers;

use Modules\UserInfo\UserAbout\Repositories\UserAboutRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteUserAboutHandler
{
    public function __construct(
        private UserAboutRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteUserAbout($id);
    }
}
