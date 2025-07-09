<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Presenters;

use Modules\RoleAndPermission\Models\Permission;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\User\Models\User;

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
            'status' => $this->permission->status,
            'user_count' => $this->getUserCount(),
            "key" => $this->permission->key,
        ];
    }

    /**
     * Get the count of users who have this permission
     * This counts both directly assigned permissions and permissions via roles
     *
     * @return int
     */
    protected function getUserCount(): int
    {
        // Count users who have this permission directly or through a role
        return User::permission($this->permission->name)->count();
    }
}
