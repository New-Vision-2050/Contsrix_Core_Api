<?php

declare(strict_types=1);

namespace Modules\UserInfo\BankAccount\Handlers;

use Modules\UserInfo\BankAccount\Commands\UpdateBankAccountCommand;
use Modules\UserInfo\BankAccount\Commands\UpdateTypeBankAccountCommand;
use Modules\UserInfo\BankAccount\Repositories\BankAccountRepository;

class UpdateTypeBankAccountHandler
{
    public function __construct(
        private BankAccountRepository $repository,
    ) {
    }

    public function handle(UpdateTypeBankAccountCommand $updateBankAccountCommand)
    {
        $this->repository->updateBankAccount($updateBankAccountCommand->getId(), $updateBankAccountCommand->toArray());
    }
}
