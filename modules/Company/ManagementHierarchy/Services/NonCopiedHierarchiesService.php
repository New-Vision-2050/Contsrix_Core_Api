<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Company\ManagementHierarchy\DTO\GetNonCopiedHierarchiesDTO;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;

class NonCopiedHierarchiesService
{
    public function __construct(
        private ManagementHierarchyRepository $repository,
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
        return $this->repository->getNonCopiedHierarchies(
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
        return $this->repository->getAllNonCopiedHierarchies();
    }
}
