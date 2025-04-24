<?php

declare(strict_types=1);

namespace Modules\Country\Presenters;

use Modules\Country\Models\Country;
use BasePackage\Shared\Presenters\AbstractPresenter;

class CountryCurrencyPresenter extends AbstractPresenter
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
            'currency_name' => $this->country->currency_name ,
            "currency_symbol"=> $this->country->currency_symbol,
        ];
    }
}
