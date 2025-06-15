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
<<<<<<< HEAD
            'is_super_admin' => $this->user->hasRole("super-admin")||$this->user->is_owner?1:0,
=======
>>>>>>> 7be6c72c (merge with stage (first version ))
            'phone' => $this->user->phone,
        ];
    }
}
