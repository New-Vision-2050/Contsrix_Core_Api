<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Feature\Services;

use Illuminate\Support\Collection;
use Modules\SubscriptionSystem\Feature\DTO\CreateFeatureDTO;
use Modules\SubscriptionSystem\Feature\Models\Feature;
use Modules\SubscriptionSystem\Feature\Repositories\FeatureRepository;
use Ramsey\Uuid\UuidInterface;

class FeatureCRUDService
{
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
        return $this->repository->getFeature(
            id: $id,
        );
    }
}
