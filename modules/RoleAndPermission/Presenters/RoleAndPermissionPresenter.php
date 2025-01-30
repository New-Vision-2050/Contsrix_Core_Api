<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Presenters;

use Modules\RoleAndPermission\Models\RoleAndPermission;
use BasePackage\Shared\Presenters\AbstractPresenter;

class RoleAndPermissionPresenter extends AbstractPresenter
{
    private RoleAndPermission $roleAndPermission;

    public function __construct(RoleAndPermission $roleAndPermission)
    {
        $this->roleAndPermission = $roleAndPermission;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->roleAndPermission->id,
            'name' => $this->roleAndPermission->name,
        ];
    }
}
