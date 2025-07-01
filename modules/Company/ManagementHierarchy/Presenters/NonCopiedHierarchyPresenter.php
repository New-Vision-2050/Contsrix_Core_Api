<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\User\Presenters\UserPresenter;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyPresenter;

class NonCopiedHierarchyPresenter extends AbstractPresenter
{
    private ManagementHierarchy $managementHierarchy;

    public function __construct(ManagementHierarchy $managementHierarchy)
    {
        $this->managementHierarchy = $managementHierarchy;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->managementHierarchy->id,
            'name' => $this->managementHierarchy->name,
            'type' => $this->managementHierarchy->type,
            'parent_id' => $this->managementHierarchy->parent_id,
            'company_id' => $this->managementHierarchy->company_id,
            'is_active' => $this->managementHierarchy->is_active,
            'is_main' => $this->managementHierarchy->is_main,
            'manager' => $this->managementHierarchy->user ? (new UserPresenter($this->managementHierarchy->user))->present() : null,
            'detail' => $this->managementHierarchy->detail ? [
                'id' => $this->managementHierarchy->detail->id,
                'description' => $this->managementHierarchy->detail->description,
                'is_copied' => $this->managementHierarchy->detail->is_copied,
                'reference_user_id' => $this->managementHierarchy->detail->reference_user_id,
                'reference_department_id' => $this->managementHierarchy->detail->reference_department_id,
                'branch_id' => $this->managementHierarchy->detail->branch_id,

            ] : null,
//            'copies' => $this->managementHierarchy->clones?->map(function ($clone) {
//                return [
//                    'id' => $clone->id,
//                    'description' => $clone->description,
//                    'is_copied' => $clone->is_copied,
//                ];
//            })->toArray(),
            'users_count' => $this->managementHierarchy->clones->sum(function ($clone) {
                return $clone->managementHierarchy ? ($clone->managementHierarchy->users_count ?? 0) : 0;
            })

        ];
    }
}
