<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Presenters;

use Modules\RoleAndPermission\Models\Permission;
use Modules\RoleAndPermission\Models\RoleAndPermission;
use BasePackage\Shared\Presenters\AbstractPresenter;

class PermissionPresenter extends AbstractPresenter
{
    private Permission $permission;

    public function __construct(Permission $permission)
    {
        $this->permission = $permission;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->permission->id,
            'name' => $this->permission->name,
        ];
    }
}
