<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Services;

use Modules\RoleAndPermission\DTO\RoleWidgetsDataDTO;
use Modules\RoleAndPermission\Repositories\RoleRepository;

class RoleService
{
    public function __construct(private readonly RoleRepository $repository)
    {
    }

    public function getRoleWidgetsData(): RoleWidgetsDataDTO
    {
        return $this->repository->getRoleWidgetsData();
    }
}
