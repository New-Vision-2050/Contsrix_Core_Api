<?php

declare(strict_types=1);

namespace Modules\User\Presenters;

use Modules\User\Models\User;
use BasePackage\Shared\Presenters\AbstractPresenter;

class UserPresenter extends AbstractPresenter
{
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
        ];
    }
}
