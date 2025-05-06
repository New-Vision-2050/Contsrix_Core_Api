<?php

declare(strict_types=1);

namespace Modules\JobTitle\Services;

use Illuminate\Support\Collection;
use Modules\JobTitle\DTO\CreateJobTitleDTO;
use Modules\JobTitle\Models\JobTitle;
use Modules\JobTitle\Repositories\JobTitleRepository;
use Ramsey\Uuid\UuidInterface;

class JobTitleCRUDService
{
    public function __construct(
        private JobTitleRepository $repository,
    ) {
    }

    public function create(CreateJobTitleDTO $createJobTitleDTO): JobTitle
    {
         return $this->repository->createJobTitle($createJobTitleDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function listAll(): Collection
    {
        return $this->repository->getAllJobTitles();
    }

    public function get(UuidInterface $id): JobTitle
    {
        return $this->repository->getJobTitle(
            id: $id,
        );
    }
}
