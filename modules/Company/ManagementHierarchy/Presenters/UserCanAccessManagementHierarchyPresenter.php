<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Presenters;

use Modules\Company\ManagementHierarchy\Models\UserCanAccessManagementHierarchy;
use BasePackage\Shared\Presenters\AbstractPresenter;

class UserCanAccessManagementHierarchyPresenter extends AbstractPresenter
{
    private UserCanAccessManagementHierarchy $userAccess;

    public function __construct(UserCanAccessManagementHierarchy $userAccess)
    {
        $this->userAccess = $userAccess;
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->userAccess->id,
            'user_id' => $this->userAccess->user_id,
            'management_hierarchy_id' => $this->userAccess->management_hierarchy_id,
            'created_at' => $this->userAccess->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->userAccess->updated_at?->format('Y-m-d H:i:s'),
        ];

        // Add user details if the relationship is loaded
        if ($this->userAccess->relationLoaded('user') && $this->userAccess->user) {
            $data['user'] = [
                'id' => $this->userAccess->user->id,
                'name' => $this->userAccess->user->name,
                'email' => $this->userAccess->user->email,
            ];
        }

        // Add management hierarchy (branch) details if the relationship is loaded
        if ($this->userAccess->relationLoaded('managementHierarchy') && $this->userAccess->managementHierarchy) {
            $data['branch'] = [
                'id' => $this->userAccess->managementHierarchy->id,
                'name' => $this->userAccess->managementHierarchy->name,
                'type' => $this->userAccess->managementHierarchy->type,
                'is_main' => $this->userAccess->managementHierarchy->is_main,
            ];
        }

        return $data;
    }
}
