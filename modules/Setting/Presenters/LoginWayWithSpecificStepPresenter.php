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

    public function __construct(public UuidInterface $loginWay,public $step=null,public $user=null)
    {
    }

    protected function present(bool $isListing = false): array
    {
        $loginWay = LoginWay::find($this->loginWay);
        $email = $this->user->email;
        $phone = $this->user->phone;
        $type = "mail";

        $by = $this->user != null ? substr($email, 0, 2) . str_repeat('*', strlen($email) - 5) . substr($email, -3) : null;
        if ( $this?->step?->drivers &&in_array("sms", $this->step->drivers) && $this->user) {
            $by = substr($phone, 0, 2) . str_repeat('*', strlen($phone) - 5) . substr($phone, -3);
            $type = "sms";
        }

        return [
            'id' => $loginWay->id,
            'name' => $loginWay->name,
            'step' => $this->step != null ?
                [
                    "login_option"=>$this->step->login_option,
                    "login_option_alternatives"=>$this->step->login_option_alternatives
                ]:null,
            'by' => $by,
            'type' => $type
        ];
    }
}
