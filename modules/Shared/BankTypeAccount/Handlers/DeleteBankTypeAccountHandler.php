<?php

declare(strict_types=1);

namespace Modules\Shared\BankTypeAccount\Handlers;

use Modules\Shared\BankTypeAccount\Repositories\BankTypeAccountRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteBankTypeAccountHandler
{
    public function __construct(
        private BankTypeAccountRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteBankTypeAccount($id);
    }
}
