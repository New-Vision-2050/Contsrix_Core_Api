<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Illuminate\Support\Collection;
use Modules\RoleAndPermission\Models\Permission;
use Modules\RoleAndPermission\Models\Role;
use Modules\User\DTO\CreateUserDTO;
use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;
use Ramsey\Uuid\UuidInterface;

class UserRoleAndPermissionService
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }



    public function getRoles(UuidInterface $id)
    {
        return $this->userRepository->getRoles(
            id: $id,
        );
    }

    public function getPermissions(UuidInterface $id)
    {
        return $this->userRepository->getPermissions(
            id: $id,
        );
    }
}
