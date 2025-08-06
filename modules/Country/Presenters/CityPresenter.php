<?php

declare(strict_types=1);

namespace Modules\Country\Presenters;

use Modules\Country\Models\Country;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Country\Models\City;

class CityPresenter extends AbstractPresenter
{
    private City $city;

    public function __construct(City $city)
    {
        $this->city = $city;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->city->id,
            'name' => $this->city->name ,
            'latitude' => $this->city->latitude,
            'longitude' => $this->city->longitude,

        ];
    }
}
