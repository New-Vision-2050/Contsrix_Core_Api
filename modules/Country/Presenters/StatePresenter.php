<?php

declare(strict_types=1);

namespace Modules\Country\Presenters;

use Modules\Country\Models\State;
use BasePackage\Shared\Presenters\AbstractPresenter;

class StatePresenter extends AbstractPresenter
{
    private State $State;

    public function __construct(State $State)
    {
        $this->State = $State;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->State->id,
            'name' => $this->State->name ,
            "flag"=> $this->State->flag,
             "country_id" => $this->State->country_id,
             "country_code" => $this->State->country_code,
            "type"=> $this->State->type,
            "latitude"=> $this->State->latitude,
            "longitude"=> $this->State->longitude
        ];
    }
}
