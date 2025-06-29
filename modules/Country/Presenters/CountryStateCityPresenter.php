<?php

declare(strict_types=1);

namespace Modules\Country\Presenters;

use Modules\Country\Models\City;
use Modules\Country\Models\Country;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Country\Models\State;

class CountryStateCityPresenter extends AbstractPresenter
{
    private $country;
    private $state;
    private $city;
    private $neighborhood;
    private $postalCode;
    private $route;


    public function __construct(
        $country = null,
        $state = null,
        $city = null,
        $neighborhood = null,
        $postalCode = null,
        $route = null,
        private $aiSupported = null
    ) {
        $this->country = $country;
        $this->state = $state;
        $this->city = $city;
        $this->neighborhood = $neighborhood;
        $this->postalCode = $postalCode;
        $this->route = $route;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            "country" => [
                'id' => $this->country?->id,
                'name' => $this->country?->name,
                "status" => $this->country?->status,
            ],
            "state" => [
                'id' => $this->state?->id,
                'name' => $this->state?->name,
            ],
            "city" => [
                'id' => $this->city?->id,
                'name' => $this->city?->name,
            ],
            "neighborhood" => $this->neighborhood,
            "postal_code" => $this->postalCode,
            "route" => $this->route


        ];
    }
}
