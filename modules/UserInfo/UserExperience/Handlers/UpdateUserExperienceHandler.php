<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserExperience\Handlers;

use Modules\UserInfo\UserExperience\Commands\UpdateUserExperienceCommand;
use Modules\UserInfo\UserExperience\Repositories\UserExperienceRepository;

class UpdateUserExperienceHandler
{
    public function __construct(
        private UserExperienceRepository $repository,
    ) {
    }

    public function handle(UpdateUserExperienceCommand $updateUserExperienceCommand)
    {
        $this->repository->updateUserExperience($updateUserExperienceCommand->getId(), $updateUserExperienceCommand->toArray());
    }
}
