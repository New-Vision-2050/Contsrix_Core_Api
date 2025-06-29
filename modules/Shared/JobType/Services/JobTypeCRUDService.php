<?php

declare(strict_types=1);

namespace Modules\Shared\JobType\Services;

use Illuminate\Support\Collection;
use Modules\Shared\JobType\DTO\CreateJobTypeDTO;
use Modules\Shared\JobType\DTO\CreateJobTypeWithCompanyDTO;
use Modules\Shared\JobType\Models\JobType;
use Modules\Shared\JobType\Repositories\JobTypeRepository;
use Ramsey\Uuid\UuidInterface;

class JobTypeCRUDService
{
    public function __construct(
        private JobTypeRepository $repository,
    ) {
    }

    public function create(CreateJobTypeDTO $createJobTypeDTO): JobType
    {
         return $this->repository->createJobType($createJobTypeDTO->toArray());
    }
    public function createWithCompany(CreateJobTypeWithCompanyDTO $createJobTypeWithCompanyDTO): JobType
    {
         return $this->repository->createJobType($createJobTypeWithCompanyDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10 , $sort ,$order): array
    {
        return $this->repository->withoutScopePaginated(
            page: $page,
            perPage: $perPage,
            conditions: [],
            sort: $sort,
            order: $order
        );
    }

    public function listAll(): Collection
    {
        return $this->repository->getAllJobTypes();
    }

    public function get(UuidInterface $id): JobType
    {
        return $this->repository->getJobType(
            id: $id,
        );
    }

    /**
     * Get job types for export with optional filtering
     *
     * @param array $filters
     * @return Collection
     */
    public function getForExport(array $filters = []): Collection
    {
        return $this->repository->getForExport($filters);
    }
}
