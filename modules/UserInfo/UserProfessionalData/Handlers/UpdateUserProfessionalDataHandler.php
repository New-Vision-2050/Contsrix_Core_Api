<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserProfessionalData\Handlers;

use Modules\UserInfo\UserProfessionalData\Commands\UpdateUserProfessionalDataCommand;
use Modules\UserInfo\UserProfessionalData\Repositories\UserProfessionalDataRepository;

class UpdateUserProfessionalDataHandler
{
    public function __construct(
        private UserProfessionalDataRepository $repository,
    ) {
    }

    public function handle(UpdateUserProfessionalDataCommand $updateUserProfessionalDataCommand)
    {
        $this->repository->updateUserProfessionalData($updateUserProfessionalDataCommand->getId(), $updateUserProfessionalDataCommand->toArray());
    }
}
