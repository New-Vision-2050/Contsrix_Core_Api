<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Feature\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\SubscriptionSystem\Feature\Models\Feature;

/**
 * @property Feature $model
 * @method Feature findOneOrFail($id)
 * @method Feature findOneByOrFail(array $data)
 */
class FeatureRepository extends BaseRepository
{
    public function __construct(Feature $model)
    {
        parent::__construct($model);
    }

    public function getFeatureList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getFeature(UuidInterface $id): Feature
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createFeature(array $data): Feature
    {
        return $this->create($data);
    }

    public function updateFeature(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteFeature(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Get all permissions associated with a set of features
     *
     * @param array $featureIds
     * @return \Illuminate\Support\Collection
     */
    public function getPermissionsByFeatures(array $featureIds): Collection
    {
        // Check if featureIds is empty
        if (empty($featureIds)) {
            return collect([]);
        }

        // Get all features with their permissions
        $features = Feature::whereIn('id', $featureIds)
            ->with('permissions')
            ->get();

        // Collect all permissions from all features
        $permissions = collect();
        foreach ($features as $feature) {
            $permissions = $permissions->merge($feature->permissions);
        }

        return $permissions;
    }
}
