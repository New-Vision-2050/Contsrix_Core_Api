<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\Presenters\Dashboard;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\EcoAddress\Models\EcoAddress;
use Modules\Country\Presenters\CityPresenter;
use Modules\Country\Presenters\CountryPresenter;

class EcoAddressDashboardPresenter extends AbstractPresenter
{
    private EcoAddress $ecoAddress;

    public function __construct(EcoAddress $ecoAddress)
    {
        $this->ecoAddress = $ecoAddress;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->ecoAddress->id,
            'first_name' => $this->ecoAddress->first_name,
            'last_name' => $this->ecoAddress->last_name,
            'email' => $this->ecoAddress->email,
            'phone_code' => $this->ecoAddress->phone_code,
            'phone' => $this->ecoAddress->phone,
            'country' => $this->ecoAddress->country? (new CountryPresenter($this->ecoAddress->country))->getData() : null,
            'city' => $this->ecoAddress->city? (new CityPresenter($this->ecoAddress->city))->getData() : null,
            'state' => $this->ecoAddress->state? [
                'id' => $this->ecoAddress->state->id,
                'name' => $this->ecoAddress->state->name,
            ] : null,
            'address' => $this->ecoAddress->address,
            'zip_code' => $this->ecoAddress->zip_code,
            'type' => $this->ecoAddress->type,
            'is_default' => (int)$this->ecoAddress->is_default,
        ];
    }
}
