<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Warehous\Presenters;

use Modules\Ecommerce\Warehous\Models\Warehous;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Country\Presenters\CityPresenter;
use Modules\Country\Presenters\CountryPresenter;

class WarehousPresenter extends AbstractPresenter
{
    private Warehous $warehous;

    public function __construct(Warehous $warehous)
    {
        $this->warehous = $warehous;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->warehous->id,
            'name' => $this->warehous->name,
            'is_default' => (int) $this->warehous->is_default,
            'country' => $this->warehous->country ?( new CountryPresenter($this->warehous->country))->getData() : null,
            'city' => $this->warehous->city ?( new CityPresenter($this->warehous->city))->getData() : null,
            'district' => $this->warehous->district,
            'street' => $this->warehous->street,
            'latitude' => $this->warehous->latitude,
            'longitude' => $this->warehous->longitude,
        ];
    }
}
