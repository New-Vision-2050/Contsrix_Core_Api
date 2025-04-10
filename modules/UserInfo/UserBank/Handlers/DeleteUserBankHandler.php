<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserBank\Handlers;

use Modules\UserInfo\UserBank\Repositories\UserBankRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteUserBankHandler
{
    public function __construct(
        private UserBankRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteUserBank($id);
    }
}
