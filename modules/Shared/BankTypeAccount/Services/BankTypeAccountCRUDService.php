<?php

declare(strict_types=1);

namespace Modules\Shared\BankTypeAccount\Services;

use Illuminate\Support\Collection;
use Modules\Shared\BankTypeAccount\DTO\CreateBankTypeAccountDTO;
use Modules\Shared\BankTypeAccount\Models\BankTypeAccount;
use Modules\Shared\BankTypeAccount\Repositories\BankTypeAccountRepository;
use Ramsey\Uuid\UuidInterface;

class BankTypeAccountCRUDService
{
    public function __construct(
        private BankTypeAccountRepository $repository,
    ) {
    }

    public function create(CreateBankTypeAccountDTO $createBankTypeAccountDTO): BankTypeAccount
    {
         return $this->repository->createBankTypeAccount($createBankTypeAccountDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): BankTypeAccount
    {
        return $this->repository->getBankTypeAccount(
            id: $id,
        );
    }
}
