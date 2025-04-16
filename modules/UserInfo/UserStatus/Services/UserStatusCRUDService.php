<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserStatus\Services;

use Illuminate\Support\Collection;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\UserInfo\UserStatus\DTO\CreateUserStatusDTO;
use Modules\UserInfo\UserStatus\Models\UserStatus;
use Modules\UserInfo\UserStatus\Repositories\UserStatusRepository;
use Ramsey\Uuid\UuidInterface;

class UserStatusCRUDService
{
    public function __construct(
        private UserStatusRepository $repository,
    ) {
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
        return $this->repository->getUserStatus($companyId, $globalId);
    }
}
