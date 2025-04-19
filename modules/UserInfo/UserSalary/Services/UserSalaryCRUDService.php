<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserSalary\Services;

use Illuminate\Support\Collection;
use Modules\UserInfo\UserSalary\DTO\CreateUserSalaryDTO;
use Modules\UserInfo\UserSalary\Models\UserSalary;
use Modules\UserInfo\UserSalary\Repositories\UserSalaryRepository;
use Ramsey\Uuid\UuidInterface;

class UserSalaryCRUDService
{
    public function __construct(
        private UserSalaryRepository $repository,
    ) {
    }

    public function create(CreateUserSalaryDTO $createUserSalaryDTO): UserSalary
    {
         return $this->repository->createUserSalary($createUserSalaryDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $companyId,UuidInterface $globalId)
    {
        return $this->repository->getUserSalary($companyId, $globalId);
    }
}
