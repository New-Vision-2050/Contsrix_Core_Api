<?php

declare(strict_types=1);

namespace Modules\User\Presenters;

use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyPresenter;
use Modules\User\Models\User;
use BasePackage\Shared\Presenters\AbstractPresenter;

class UserRolesPresenter extends AbstractPresenter
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
            'phone' => $this->user->phone,
            "branches"=>ManagementHierarchyPresenter::collection($this->user->managementHierarchies),
            "status"=>$this->user->status
        ];
    }
}
