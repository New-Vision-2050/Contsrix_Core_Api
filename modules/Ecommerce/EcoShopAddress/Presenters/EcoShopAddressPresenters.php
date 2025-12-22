<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShopAddress\Presenters;

use Modules\Ecommerce\EcoShopAddress\Models\EcoShopAddress;
use BasePackage\Shared\Presenters\AbstractPresenter;

class EcoShopAddressPresenters extends AbstractPresenter
{
    private EcoShopAddress $ecoShopAddress;

    public function __construct(EcoShopAddress $ecoShopAddress)
    {
        $this->ecoShopAddress = $ecoShopAddress;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->ecoShopAddress->id,
            'company_id' => $this->ecoShopAddress->company_id,
            'country' => $this->ecoShopAddress->country,
            'city' => $this->ecoShopAddress->city,
            'district' => $this->ecoShopAddress->district,
            'street' => $this->ecoShopAddress->street,
            'building_number' => $this->ecoShopAddress->building_number,
            'postal_code' => $this->ecoShopAddress->postal_code,
            'additional_number' => $this->ecoShopAddress->additional_number,
            'latitude' => $this->ecoShopAddress->latitude,
            'longitude' => $this->ecoShopAddress->longitude,
            'full_address' => $this->ecoShopAddress->full_address,
            'address_notes' => $this->ecoShopAddress->address_notes,
            'formatted_address' => $this->ecoShopAddress->formatted_address,
            'coordinates' => $this->ecoShopAddress->coordinates,
            'created_at' => $this->ecoShopAddress->created_at,
            'updated_at' => $this->ecoShopAddress->updated_at,
        ];
    }
}
