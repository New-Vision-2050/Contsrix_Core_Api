<?php

declare(strict_types=1);

namespace Modules\Setting\Presenters;

use Modules\Setting\Models\LoginWay;
use Modules\Setting\Models\Setting;
use BasePackage\Shared\Presenters\AbstractPresenter;

class LoginWayPresenter extends AbstractPresenter
{

    public function __construct(public LoginWay $loginWay)
    {
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'name' => $this->loginWay->name,
            'steps' => LoginOptionPresenter::collection($this->loginWay->loginWaySteps),
        ];
    }
}
