<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Services;

use Illuminate\Support\Collection;
use Modules\UserInfo\UserPrivilege\DTO\CreateUserPrivilegeDTO;
use Modules\UserInfo\UserPrivilege\Models\UserPrivilege;
use Modules\UserInfo\UserPrivilege\Repositories\UserPrivilegeRepository;
use Ramsey\Uuid\UuidInterface;

class UserPrivilegeCRUDService
{
    public function __construct(
        private UserPrivilegeRepository $repository,
    ) {
    }

    public function create(CreateUserPrivilegeDTO $createUserPrivilegeDTO): UserPrivilege
    {
         return $this->repository->createUserPrivilege($createUserPrivilegeDTO->toArray());
    }

    public function list(UuidInterface $companyId,UuidInterface $globalId,int $page = 1, int $perPage = 10): array
    {
        return $this->repository->getUserPrivilegeList($companyId, $globalId, $page, $perPage);
    }

    public function get(UuidInterface $id): UserPrivilege
    {
        return $this->repository->getUserPrivilege(
            id: $id,
        );
    }
}
