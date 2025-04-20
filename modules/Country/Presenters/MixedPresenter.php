<?php

declare(strict_types=1);

namespace Modules\Country\Presenters;

use Modules\Country\Models\Country;
use BasePackage\Shared\Presenters\AbstractPresenter;

class MixedPresenter extends AbstractPresenter
{
    private  $country;

    public function __construct( $country)
    {
        $this->country = $country;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->country->id,
            'name' => $this->country->name ,


        ];
    }
}
