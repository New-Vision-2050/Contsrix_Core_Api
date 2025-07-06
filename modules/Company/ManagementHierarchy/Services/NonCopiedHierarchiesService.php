<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Company\ManagementHierarchy\DTO\GetNonCopiedHierarchiesDTO;
<<<<<<< HEAD
use Modules\Company\ManagementHierarchy\Models\SourceManagementHierarchy;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
=======
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;
>>>>>>> 4d33c9eb (merge roles with subscription)

class NonCopiedHierarchiesService
{
    public function __construct(
<<<<<<< HEAD
        private ManagementHierarchyRepository $managementHierarchyRepository,
=======
        private ManagementHierarchyRepository $repository,
>>>>>>> 4d33c9eb (merge roles with subscription)
    ) {
    }

    /**
     * Get management hierarchies where detail.is_copied = 0 with detail.managementHierarchy relationship
     *
     * @param GetNonCopiedHierarchiesDTO $dto
     * @return array
     */
    public function getNonCopiedHierarchies(GetNonCopiedHierarchiesDTO $dto): array
    {
<<<<<<< HEAD
        return $this->managementHierarchyRepository->getNonCopiedHierarchies(
=======
        return $this->repository->getNonCopiedHierarchies(
>>>>>>> 4d33c9eb (merge roles with subscription)
            page: $dto->page,
            perPage: $dto->perPage
        );
    }

    /**
     * Get all non-copied hierarchies without pagination
     *
     * @return Collection
     */
    public function getAllNonCopiedHierarchies(): Collection
    {
<<<<<<< HEAD
        return $this->managementHierarchyRepository->getAllNonCopiedHierarchies();
    }

    /**
     * Find a non-copied hierarchy by ID
     *
     * @param  $id
     * @return ManagementHierarchy|null
     */
    public function findNonCopiedHierarchyById( $id): ?SourceManagementHierarchy
    {
        return $this->managementHierarchyRepository->findNonCopiedHierarchyById($id);
=======
        return $this->repository->getAllNonCopiedHierarchies();
>>>>>>> 4d33c9eb (merge roles with subscription)
    }
}
