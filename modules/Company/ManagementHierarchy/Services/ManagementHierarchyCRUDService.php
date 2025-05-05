<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Services;

use Modules\Company\ManagementHierarchy\DTO\CreateBranchDTO;
use Modules\Company\ManagementHierarchy\DTO\CreateDepartmentDTO;
use Modules\Company\ManagementHierarchy\DTO\CreateManagementDTO;
use Modules\Company\ManagementHierarchy\DTO\CreateManagementHierarchyDTO;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;

class ManagementHierarchyCRUDService
{
    public function __construct(
        private ManagementHierarchyRepository $repository,
    ) {
    }

    public function create(CreateManagementHierarchyDTO $createManagementHierarchyDTO): ManagementHierarchy
    {
         return $this->repository->createManagementHierarchy($createManagementHierarchyDTO->toArray());
    }

    public function createBranch(CreateBranchDTO $createBranchDTO): ManagementHierarchy
    {
         return $this->repository->createBranch($createBranchDTO->branchToArray(), $createBranchDTO->AddressToArray());
    }



    public function createManagement(CreateManagementDTO $createManagementDTO): ManagementHierarchy
    {
         return $this->repository->createManagement($createManagementDTO->managementToArray(),$createManagementDTO->managementDetailToArray());
    }

    public function createDepartment(CreateDepartmentDTO $createDepartmentDTO): ManagementHierarchy
    {
         return $this->repository->createDepartment($createDepartmentDTO->departmentToArray(),$createDepartmentDTO->departmentDetailToArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }
    public function listWithoutPagination()
    {
        return $this->repository->getAll();
    }

    public function listCompany($type,int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            ['type'=>$type],
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(int $id): ManagementHierarchy
    {
        return $this->repository->getManagementHierarchy(
            id: $id,
        );
    }

    public function getTree()
    {
        return $this->repository->getTree();
    }

}
