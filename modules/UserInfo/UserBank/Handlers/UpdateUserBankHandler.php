<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserBank\Handlers;

use Modules\UserInfo\UserBank\Commands\UpdateUserBankCommand;
use Modules\UserInfo\UserBank\Repositories\UserBankRepository;

class UpdateUserBankHandler
{
    public function __construct(
        private UserBankRepository $repository,
    ) {
    }

    public function handle(UpdateUserBankCommand $updateUserBankCommand)
    {
        $this->repository->updateUserBank($updateUserBankCommand->getId(), $updateUserBankCommand->toArray());
    }
}
