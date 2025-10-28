<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Services;

use Modules\Ecommerce\Banner\DTO\CreateStoreBranchDTO;
use Modules\Ecommerce\Banner\Models\StoreBranch;
use Modules\Ecommerce\Banner\Repositories\StoreBranchRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class StoreBranchCRUDService
{
    use HasExportService;

    public function __construct(
        private StoreBranchRepository $repository,
    ) {
    }

    public function create(CreateStoreBranchDTO $createStoreBranchDTO): StoreBranch
    {
        return $this->repository->createStoreBranch($createStoreBranchDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
            filters: $filters,
        );
    }

    public function get(UuidInterface $id): StoreBranch
    {
        return $this->repository->getStoreBranch($id);
    }

    public function update(UuidInterface $id, array $data): StoreBranch
    {
        return $this->repository->updateStoreBranch($id, $data);
    }

    public function delete(UuidInterface $id): bool
    {
        return $this->repository->deleteStoreBranch($id);
    }

    public function toggleStatus(UuidInterface $id): StoreBranch
    {
        return $this->repository->toggleStatus($id);
    }

    public function getByType(string $type): array
    {
        return $this->repository->getByType($type);
    }

    public function getByCountry(UuidInterface $countryId): array
    {
        return $this->repository->getByCountry($countryId);
    }

    public function getActiveStoreBranches(): array
    {
        return $this->repository->getActiveStoreBranches();
    }

    public function searchByName(string $name): array
    {
        return $this->repository->searchByName($name);
    }
}
