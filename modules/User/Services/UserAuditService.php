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

class UserAuditService
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }



    public function getAudits(UuidInterface $id)
    {
        return $this->userRepository->getAllAudites(
            id: $id,
        );
    }


}
