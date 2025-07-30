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
                if ($i == 1 && str_contains($nameParts[$i], "*")) {
                    $resources = explode('*', $nameParts[$i]);
                    $isUuid = preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $resources[1]);
                    if ($isUuid) {
                        // If it's a UUID, group by the part before asterisk
                        $translatedName .= " " . $resources[0];
                    } else {
                        // If it's not a UUID, group by the part after asterisk
                        $translatedName .= " " . __('names.' . $resources[0]);
                        $translatedName .= " " . __('names.' . $resources[1]);
                    }


                    break;
                }
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
