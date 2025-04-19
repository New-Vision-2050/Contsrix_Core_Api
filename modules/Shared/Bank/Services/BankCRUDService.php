<?php

declare(strict_types=1);

namespace Modules\Shared\Bank\Services;

use Illuminate\Support\Collection;
use Modules\Shared\Bank\DTO\CreateBankDTO;
use Modules\Shared\Bank\Models\Bank;
use Modules\Shared\Bank\Repositories\BankRepository;
use Ramsey\Uuid\UuidInterface;

class BankCRUDService
{
    public function __construct(
        private BankRepository $repository,
    ) {
    }

    public function create(CreateBankDTO $createBankDTO): Bank
    {
         return $this->repository->createBank($createBankDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Bank
    {
        return $this->repository->getBank(
            id: $id,
        );
    }
}
