<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Subscription\Services;

use Modules\SubscriptionSystem\Subscription\Repositories\FeatureRepository;
use Modules\RoleAndPermission\Models\Permission;
use Illuminate\Support\Collection;

class FeatureCRUDService
{
    public function __construct(
        private FeatureRepository $featureRepository
    ) {
    }

    /**
     * Get non-redundant permissions for a set of features
     *
     * @param array $featureIds
     * @return Collection
     */
    public function getNonRedundantPermissionsByFeatures(array $featureIds): Collection
    {
        // Get permissions for all the features
        $allPermissions = $this->featureRepository->getPermissionsByFeatures($featureIds);

        // Remove redundant permissions (duplicates)
        return $allPermissions->unique('id');
    }
}
