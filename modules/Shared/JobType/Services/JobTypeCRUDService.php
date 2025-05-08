<?php

declare(strict_types=1);

namespace Modules\Shared\JobType\Services;

use Illuminate\Support\Collection;
use Modules\Shared\JobType\DTO\CreateJobTypeDTO;
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

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->withoutScopePaginated( page: $page,
            perPage: $perPage);
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
}
