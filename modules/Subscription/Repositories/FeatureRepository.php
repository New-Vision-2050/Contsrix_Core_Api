<?php

declare(strict_types=1);

namespace Modules\Subscription\Repositories;

use Illuminate\Support\Collection;
use Modules\Subscription\Models\Feature;

class FeatureRepository
{
    /**
     * Get all permissions associated with a set of features
     * 
     * @param array $featureIds
     * @return Collection
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
