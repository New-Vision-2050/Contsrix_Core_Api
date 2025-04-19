<?php

declare(strict_types=1);

namespace Modules\Shared\Privilege\Services;

use Illuminate\Support\Collection;
use Modules\Shared\Privilege\DTO\CreatePrivilegeDTO;
use Modules\Shared\Privilege\Models\Privilege;
use Modules\Shared\Privilege\Repositories\PrivilegeRepository;
use Ramsey\Uuid\UuidInterface;

class PrivilegeCRUDService
{
    public function __construct(
        private PrivilegeRepository $repository,
    ) {
    }

    public function create(CreatePrivilegeDTO $createPrivilegeDTO): Privilege
    {
         return $this->repository->createPrivilege($createPrivilegeDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Privilege
    {
        return $this->repository->getPrivilege(
            id: $id,
        );
    }
}
