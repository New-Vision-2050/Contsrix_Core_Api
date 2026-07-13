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
            "status"=> $this->State->status,
            "sms_driver" => $this->State->smsDriver?->name,
             "currency_name" => $this->State->currency_name,
            "currency_symbol"=> $this->State->currency_symbol
        ];
    }
}
