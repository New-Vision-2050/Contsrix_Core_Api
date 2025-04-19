<?php

declare(strict_types=1);

namespace Modules\UserInfo\BankAccount\Handlers;

use Modules\UserInfo\BankAccount\Repositories\BankAccountRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteBankAccountHandler
{
    public function __construct(
        private BankAccountRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteBankAccount($id);
    }
}
