<?php

declare(strict_types=1);

namespace Modules\Setting\Presenters;

use Modules\Setting\Models\LoginWayStep;
use Modules\Setting\Models\Setting;
use BasePackage\Shared\Presenters\AbstractPresenter;

class LoginOptionPresenter extends AbstractPresenter
{

    public function __construct(public LoginWayStep $loginWayStep)
    {
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'login_option' => $this->loginWayStep->login_option,
            'drivers' => $this->loginWayStep->drivers,
            'login_option_alternatives' => $this->loginWayStep->login_option_alternatives,
        ];
    }
}
