<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Presenters;

use Modules\Subscription\Package\Models\Package;
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
            'status' => $this->package->is_active,
            'features_count' => $this->package->features_count ?? 0,
            'price' => $this->package->price,
            'currency' => $this->package->currency,
            'subscription_period' => $this->package->subscription_period,
            'subscription_period_unit' => $this->package->subscription_period_unit,

            'trial_period' => $this->package->trial_period,
            'trial_period_unit' => $this->package->trial_period_unit,

            'company_fields' => $this->package->companyFields->map(fn($field) => [
                'id' => $field->id,
                'name' => $field->name,
            ]),
            'company_types' => $this->package->companyTypes->map(fn($type) => [
                'id' => $type->id,
                'name' => $type->name,
            ]),
            'countries' => $this->package->countries->map(fn($country) => [
                'id' => $country->id,
                'name' => $country->name,
                'currency' => $country->currency,
                'currency_name' => $country->currency_name,
                'currency_symbol' => $country->currency_symbol,
            ]),
        ];
    }

    public function getData(bool $isListing = false): ?array
    {
        $array = $this->present($isListing);
        foreach ($array as $key => $value) {
            if ($value === 'delete_this_row') {
                unset($array[$key]);
            }
            if ($value === 'delete_this_array') {
                return null;
            }
        }
        return $array;
    }
}
