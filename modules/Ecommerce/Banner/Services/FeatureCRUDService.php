<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Services;

use Modules\Ecommerce\Banner\DTO\CreateFeatureDTO;
use Modules\Ecommerce\Banner\Models\Feature;
use Modules\Ecommerce\Banner\Repositories\FeatureRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class FeatureCRUDService
{
    use HasExportService;

    public function __construct(
        private FeatureRepository $repository,
    ) {
    }

    public function create(CreateFeatureDTO $createFeatureDTO): Feature
    {
        return $this->repository->createFeature($createFeatureDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Feature
    {
        return $this->repository->getFeature($id);
    }

    public function update(UuidInterface $id, array $data): Feature
    {
        return $this->repository->updateFeature($id, $data);
    }

    public function delete(UuidInterface $id): bool
    {
        return $this->repository->deleteFeature($id);
    }

    public function toggleStatus(UuidInterface $id): Feature
    {
        return $this->repository->toggleStatus($id);
    }

    public function getByCompany(UuidInterface $companyId): array
    {
        $features = $this->repository->getByCompany($companyId);
        return $features->toArray();
    }

    public function getActiveFeatures(): array
    {
        $features = $this->repository->getActiveFeatures();
        return $features->toArray();
    }
}
