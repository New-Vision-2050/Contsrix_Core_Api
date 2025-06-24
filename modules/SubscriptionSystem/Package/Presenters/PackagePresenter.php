<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Package\Presenters;

use Modules\SubscriptionSystem\Package\Models\Package;
use BasePackage\Shared\Presenters\AbstractPresenter;

class PackagePresenter extends AbstractPresenter
{
    private Package $package;

    public function __construct(Package $package)
    {
        $this->package = $package;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->package->id,
            'name' => $this->package->name,
            'price' => $this->package->price,
            'currency_id' => $this->package->currency_id,
            'billing_cycle' => $this->package->billing_cycle,
            'trial_period' => $this->package->trial_period,
            'trial_period_type' => $this->package->trial_period_type,
            // 'is_active' => $this->package->is_active,
            
            // // Include related data
            'business_types' => $this->package->businessTypes,
            'countries' => $this->package->countries,
            'program_systems' => $this->package->programSystems,
        ];
    }
}
