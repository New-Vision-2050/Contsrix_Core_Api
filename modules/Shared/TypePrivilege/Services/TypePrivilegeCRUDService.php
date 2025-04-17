<?php

declare(strict_types=1);

namespace Modules\Shared\TypePrivilege\Services;

use Illuminate\Support\Collection;
use Modules\Shared\TypePrivilege\DTO\CreateTypePrivilegeDTO;
use Modules\Shared\TypePrivilege\Models\TypePrivilege;
use Modules\Shared\TypePrivilege\Repositories\TypePrivilegeRepository;
use Ramsey\Uuid\UuidInterface;

class TypePrivilegeCRUDService
{
    public function __construct(
        private TypePrivilegeRepository $repository,
    ) {
    }

    public function create(CreateTypePrivilegeDTO $createTypePrivilegeDTO): TypePrivilege
    {
         return $this->repository->createTypePrivilege($createTypePrivilegeDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): TypePrivilege
    {
        return $this->repository->getTypePrivilege(
            id: $id,
        );
    }
}
