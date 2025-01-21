<?php

declare(strict_types=1);

namespace Modules\Auth\Presenters;

use Modules\Auth\Models\Auth;
use BasePackage\Shared\Presenters\AbstractPresenter;

class AuthPresenter extends AbstractPresenter
{
    private Auth $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->auth->id,
            'name' => $this->auth->name,
        ];
    }
}
