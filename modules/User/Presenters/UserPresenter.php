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
            'is_super_admin' => $this->user->hasRole("super-admin")||$this->user->is_owner?1:0,
            'phone' => $this->user->phone,
            'management_hierarchy_id ' => $this->user->brancmanagement_hierarchy_id 
        ];
    }
}
