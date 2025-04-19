<?php

declare(strict_types=1);

namespace Modules\UserInfo\BankAccount\Handlers;

use Modules\UserInfo\BankAccount\Commands\UpdateBankAccountCommand;
use Modules\UserInfo\BankAccount\Repositories\BankAccountRepository;

class UpdateBankAccountHandler
{
    public function __construct(
        private BankAccountRepository $repository,
    ) {
    }

    public function handle(UpdateBankAccountCommand $updateBankAccountCommand)
    {
        $this->repository->updateBankAccount($updateBankAccountCommand->getId(), $updateBankAccountCommand->toArray());
    }
}
