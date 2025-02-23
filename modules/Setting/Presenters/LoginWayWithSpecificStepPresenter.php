<?php

declare(strict_types=1);

namespace Modules\Setting\Presenters;

use Illuminate\Console\Scheduling\ScheduleTestCommand;
use Modules\Setting\Models\LoginWay;
use Modules\Setting\Models\Setting;
use BasePackage\Shared\Presenters\AbstractPresenter;

class LoginWayWithSpecificStepPresenter extends AbstractPresenter
{

    public function __construct(public LoginWay $loginWay,public int $step)
    {
    }

    protected function present(bool $isListing = false): array
    {
       $step = $this->loginWay->loginWaySteps[$this->step-1];
        return [
            'id' => $this->loginWay->id,
            'name' => $this->loginWay->name,
            'steps' =>
                [
                    "login_option"=>$step->login_option,
                    "drivers"=> DriverPresenter::collection($step->drivers)
                ]
        ];
    }
}
