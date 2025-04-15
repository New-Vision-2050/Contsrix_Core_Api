<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserExperience\Handlers;

use Modules\UserInfo\UserExperience\Repositories\UserExperienceRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteUserExperienceHandler
{
    public function __construct(
        private UserExperienceRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteUserExperience($id);
    }
}
