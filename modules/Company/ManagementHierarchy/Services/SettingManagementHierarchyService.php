<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Services;

use AWS\CRT\Auth\CredentialsProvider;
use Illuminate\Database\Eloquent\Collection;
use Modules\Company\ManagementHierarchy\DTO\CreateDepartmentWithRelationsDTO;
use Modules\Company\ManagementHierarchy\DTO\GetNonCopiedHierarchiesDTO;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;

class SettingManagementHierarchyService
{
    public function __construct(
        private ManagementHierarchyRepository $repository,
    )
    {
    }

    public function createDepartmentWithRealtion(CreateDepartmentWithRelationsDTO $dto)
    {
        return $this->repository->createDepartmentWithRelations($dto->departmentToArray(), $dto->departmentDetailToArray(), [], $dto->managements);
    }

}
