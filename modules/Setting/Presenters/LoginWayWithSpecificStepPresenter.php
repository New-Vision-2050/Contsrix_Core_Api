<?php

declare(strict_types=1);

namespace Modules\Setting\Presenters;

use Illuminate\Console\Scheduling\ScheduleTestCommand;
use Modules\Setting\Models\LoginWay;
use Modules\Setting\Models\Setting;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Ramsey\Uuid\UuidInterface;

class LoginWayWithSpecificStepPresenter extends AbstractPresenter
{

    public function __construct(public UuidInterface $loginWay,public $step=null)
    {
    }

    protected function present(bool $isListing = false): array
    {
        $loginWay = LoginWay::find($this->loginWay);

        return [
            'id' => $loginWay->id,
            'name' => $loginWay->name,
            'step' =>$this->step!= null?
                [
                    "login_option"=>$this->step->login_option,
                    "login_option_alternatives"=>$this->step->login_option_alternatives
                ]:null
        ];
    }
}
