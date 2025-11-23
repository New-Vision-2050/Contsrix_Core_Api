<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Services\Website;

use Modules\Ecommerce\Banner\Models\Feature;
use Modules\Ecommerce\Banner\Repositories\FeatureRepository;
use Ramsey\Uuid\UuidInterface;

class FeatureWebsiteService
{
    public function __construct(
        private FeatureRepository $repository,
    ) {
    }

    public function list(int $page = 1, int $perPage = 10, ?string $type = null): array
    {
        $conditions = ['is_active' => true];
        
        if ($type) {
            $conditions['type'] = $type;
        }

        return $this->repository->paginated(
            conditions: $conditions,
            page: $page,
            perPage: $perPage
        );
    }

    public function get(UuidInterface $id): Feature
    {
        $feature = $this->repository->getFeature($id);
        
        if (!$feature->is_active) {
            abort(404, 'Feature not found');
        }
        
        return $feature;
    }
}

