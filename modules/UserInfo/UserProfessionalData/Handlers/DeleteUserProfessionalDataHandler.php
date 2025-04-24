<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserProfessionalData\Handlers;

use Modules\UserInfo\UserProfessionalData\Repositories\UserProfessionalDataRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteUserProfessionalDataHandler
{
    public function __construct(
        private UserProfessionalDataRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteUserProfessionalData($id);
    }
}
