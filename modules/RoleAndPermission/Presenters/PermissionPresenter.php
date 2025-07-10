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
        $nameParts = explode('.', $this->permission->name);
        $translatedName = '';
        if (count($nameParts) >= 2) {
            // Skip the first part (module name) and translate the rest
            for ($i = count($nameParts) - 1; $i >= 1; $i--) {
                $translatedName .= ($translatedName ? ' ' : '') . __('names.' . $nameParts[$i]);
            }
        } elseif (count($nameParts) == 1) {
            $translatedName = __('names.' . $nameParts[0]);
        } else {
            $translatedName = __('names.' . $this->permission->name);
        }
        return [
            'id' => $this->permission->id,
            'name' => $translatedName,
            'status' => $this->permission->status,
            'user_count' => $this->getUserCount()
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
