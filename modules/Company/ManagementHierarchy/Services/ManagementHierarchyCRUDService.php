<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Services;

use Illuminate\Support\Collection;
use Modules\Company\ManagementHierarchy\DTO\CreateBranchDTO;
use Modules\Company\ManagementHierarchy\DTO\CreateManagementHierarchyDTO;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
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
         return $this->repository->createManagementHierarchy($createManagementHierarchyDTO->toArray(),[""]);
    }

    public function createBranch(CreateBranchDTO $createBranchDTO): ManagementHierarchy
    {
         return $this->repository->createManagementHierarchy($createBranchDTO->branchToArray(), $createBranchDTO->AddressToArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function listCompany($companyId,$type,int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            ['company_id'=>$companyId ,'type'=>$type],
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): ManagementHierarchy
    {
        return $this->repository->getManagementHierarchy(
            id: $id,
        );
    }
}
