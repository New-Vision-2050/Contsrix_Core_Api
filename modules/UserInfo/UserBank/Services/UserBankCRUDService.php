<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserBank\Services;

use Illuminate\Support\Collection;
use Modules\UserInfo\UserBank\DTO\CreateUserBankDTO;
use Modules\UserInfo\UserBank\Models\UserBank;
use Modules\UserInfo\UserBank\Repositories\UserBankRepository;
use Ramsey\Uuid\UuidInterface;

class UserBankCRUDService
{
    public function __construct(
        private UserBankRepository $repository,
    ) {
    }

    public function create(CreateUserBankDTO $createUserBankDTO): UserBank
    {
         return $this->repository->createUserBank($createUserBankDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): UserBank
    {
        return $this->repository->getUserBank(
            id: $id,
        );
    }
}
