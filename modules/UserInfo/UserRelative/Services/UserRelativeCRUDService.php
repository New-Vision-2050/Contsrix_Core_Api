<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserRelative\Services;

use Illuminate\Support\Collection;
use Modules\UserInfo\UserRelative\DTO\CreateUserRelativeDTO;
use Modules\UserInfo\UserRelative\Models\UserRelative;
use Modules\UserInfo\UserRelative\Repositories\UserRelativeRepository;
use Ramsey\Uuid\UuidInterface;

class UserRelativeCRUDService
{
    public function __construct(
        private UserRelativeRepository $repository,
    ) {
    }

    public function create(CreateUserRelativeDTO $createUserRelativeDTO): UserRelative
    {
         return $this->repository->createUserRelative($createUserRelativeDTO->toArray());
    }


    public function list(UuidInterface $companyId,UuidInterface $globalId,int $page = 1, int $perPage = 10): array
    {
        return $this->repository->getUserRelativeList($companyId, $globalId, $page, $perPage);
    }

    public function get(UuidInterface $id): UserRelative
    {
        return $this->repository->getUserRelative(
            id: $id,
        );
    }
}
