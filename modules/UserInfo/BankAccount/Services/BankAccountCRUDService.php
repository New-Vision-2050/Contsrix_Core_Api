<?php

declare(strict_types=1);

namespace Modules\UserInfo\BankAccount\Services;

use Illuminate\Support\Collection;
use Modules\UserInfo\BankAccount\DTO\CreateBankAccountDTO;
use Modules\UserInfo\BankAccount\Models\BankAccount;
use Modules\UserInfo\BankAccount\Repositories\BankAccountRepository;
use Ramsey\Uuid\UuidInterface;

class BankAccountCRUDService
{
    public function __construct(
        private BankAccountRepository $repository,
    ) {
    }

    public function create(CreateBankAccountDTO $createBankAccountDTO): BankAccount
    {
         return $this->repository->createBankAccount($createBankAccountDTO->toArray());
    }

    public function list(UuidInterface $companyId,UuidInterface $globalId,int $page = 1, int $perPage = 10)//: array
    {
        return $this->repository->getBankAccountList($companyId, $globalId, $page, $perPage);
    }

    public function get(UuidInterface $id): BankAccount
    {
        return $this->repository->getBankAccount(
            id: $id,
        );
    }
}
