<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Services;

use Faker\Core\Uuid;
use Modules\Company\ManagementHierarchy\DTO\CreateBranchDTO;
use Modules\Company\ManagementHierarchy\DTO\CreateDepartmentDTO;
use Modules\Company\ManagementHierarchy\DTO\CreateManagementDTO;
use Modules\Company\ManagementHierarchy\DTO\CreateManagementHierarchyDTO;
use Modules\Company\ManagementHierarchy\DTO\CreateManagementWithRelationsDTO;
use Modules\Company\ManagementHierarchy\DTO\UpdateManagementWithRelationsDTO;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Company\ManagementHierarchy\Models\SourceManagementHierarchy;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;
use Ramsey\Uuid\UuidInterface;

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

    public function updateManagementWithLookupsForChoise(UpdateManagementWithRelationsDTO $updateManagementWithRelationsDTO): SourceManagementHierarchy
    {
        return $this->repository->updateManagementWithRelations(
            $updateManagementWithRelationsDTO->getManagementId(),
            $updateManagementWithRelationsDTO->managementToArray(),
            $updateManagementWithRelationsDTO->managementDetailToArray(),
            $updateManagementWithRelationsDTO->getDeputyManagerIds(),
            $updateManagementWithRelationsDTO->getJobTypes(),
            $updateManagementWithRelationsDTO->getJobTitles(),
            $updateManagementWithRelationsDTO->getBranches()
        );
    }

    /**
     * Delete management with all related data (job types, job titles, branches, deputy managers)
     */
    public function deleteManagementWithLookupsForChoise(int $managementId): bool
    {
        return $this->repository->deleteManagementWithRelations($managementId);
    }

    public function createBranch(CreateBranchDTO $createBranchDTO): ManagementHierarchy
    {
         return $this->repository->createBranch($createBranchDTO->branchToArray(), $createBranchDTO->AddressToArray());
    }



    public function createManagement(CreateManagementDTO $createManagementDTO): ManagementHierarchy
    {
         return $this->repository->createManagement($createManagementDTO->managementToArray(),$createManagementDTO->managementDetailToArray(),$createManagementDTO->getDeputyManagerIds());
    }

    public function createDepartment(CreateDepartmentDTO $createDepartmentDTO): ManagementHierarchy
    {
         return $this->repository->createDepartment($createDepartmentDTO->departmentToArray(),$createDepartmentDTO->departmentDetailToArray(),[],[]);
    }

    public function createManagementWithLookupsForChoise(CreateManagementWithRelationsDTO $createManagementWithRelationsDTO): SourceManagementHierarchy
    {
//        $detail =$this->repository->getDetail($createManagementWithRelationsDTO->getParentId());
        return $this->repository->createManagementWithRelations(
            $createManagementWithRelationsDTO->managementToArray(),
            $createManagementWithRelationsDTO->managementDetailToArray(),
            $createManagementWithRelationsDTO->getDeputyManagerIds(),
            $createManagementWithRelationsDTO->getJobTypes(),
            $createManagementWithRelationsDTO->getJobTitles(),
            $createManagementWithRelationsDTO->getBranches()
        );
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }
    public function listWithoutPagination($type = null)
    {
        return $this->repository->getAll($type);
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

    public function getLowerUsers(UuidInterface $id)
    {
        return $this->repository->getUserLowerLevels($id);
    }

}
