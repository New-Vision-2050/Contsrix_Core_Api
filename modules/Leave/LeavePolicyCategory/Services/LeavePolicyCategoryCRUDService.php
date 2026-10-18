<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicyCategory\Services;

use Modules\Leave\LeavePolicyCategory\DTO\CreateLeavePolicyCategoryDTO;
use Modules\Leave\LeavePolicyCategory\Models\LeavePolicyCategory;
use Modules\Leave\LeavePolicyCategory\Repositories\LeavePolicyCategoryRepository;
use Ramsey\Uuid\UuidInterface;

class LeavePolicyCategoryCRUDService
{
    public function __construct(
        private LeavePolicyCategoryRepository $repository,
    ) {
    }

    public function create(CreateLeavePolicyCategoryDTO $dto): LeavePolicyCategory
    {
        return $this->repository->createLeavePolicyCategory($dto->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): LeavePolicyCategory
    {
        return $this->repository->getLeavePolicyCategory($id);
    }
}
