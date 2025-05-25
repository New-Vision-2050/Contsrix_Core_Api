<?php

declare(strict_types=1);

namespace Modules\Shared\BankTypeAccount\Handlers;

use Modules\Shared\BankTypeAccount\Commands\UpdateBankTypeAccountCommand;
use Modules\Shared\BankTypeAccount\Repositories\BankTypeAccountRepository;

class UpdateBankTypeAccountHandler
{
    public function __construct(
        private BankTypeAccountRepository $repository,
    ) {
    }

    public function handle(UpdateBankTypeAccountCommand $updateBankTypeAccountCommand)
    {
        $this->repository->updateBankTypeAccount($updateBankTypeAccountCommand->getId(), $updateBankTypeAccountCommand->toArray());
    }
}
