<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShopAddress\Presenters;

use Modules\Ecommerce\EcoShopAddress\Models\EcoShopAddress;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Country\Presenters\CityPresenter;
use Modules\Country\Presenters\CountryPresenter;
use Modules\Shared\TimeZone\Presenters\TimeZonePresenter;

class EcoShopAddressPresenter extends AbstractPresenter
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
            'country' => $this->ecoShopAddress->country ? (new CountryPresenter($this->ecoShopAddress->country))->getData() : null,
            'city_id' => $this->ecoShopAddress->city ? (new CityPresenter($this->ecoShopAddress->city))->getData() : null,
            'time_zone_id' => $this->ecoShopAddress->time_zone ? (new TimeZonePresenter($this->ecoShopAddress->time_zone))->getData() : null,
            'district' => $this->ecoShopAddress->district,
            'street' => $this->ecoShopAddress->street,
            'building_number' => $this->ecoShopAddress->building_number,
            'postal_code' => $this->ecoShopAddress->postal_code,
            'latitude' => $this->ecoShopAddress->latitude,
            'longitude' => $this->ecoShopAddress->longitude,
        ];
    }
}
