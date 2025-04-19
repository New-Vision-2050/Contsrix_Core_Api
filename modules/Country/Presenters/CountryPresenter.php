<?php

declare(strict_types=1);

namespace Modules\Country\Presenters;

use Modules\Country\Models\Country;
use BasePackage\Shared\Presenters\AbstractPresenter;

class CountryPresenter extends AbstractPresenter
{
    private Country $country;

    public function __construct(Country $country)
    {
        $this->country = $country;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->country->id,
            'name' => $this->country->name ,
            "status"=> $this->country->status,
            "sms_driver" => $this->country->smsDriver?->name

        ];
    }
}
