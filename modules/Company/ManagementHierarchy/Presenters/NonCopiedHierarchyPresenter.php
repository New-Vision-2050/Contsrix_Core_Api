<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Company\ManagementHierarchy\Models\SourceManagementHierarchy;
use Modules\User\Presenters\UserPresenter;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyPresenter;

class NonCopiedHierarchyPresenter extends AbstractPresenter
{
    private SourceManagementHierarchy $sourceManagementHierarchy;

    public function __construct(SourceManagementHierarchy $sourceManagementHierarchy)
    {
        $this->sourceManagementHierarchy = $sourceManagementHierarchy;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->sourceManagementHierarchy->id,
            'code_id' => $this->sourceManagementHierarchy->id,
            'name' => $this->sourceManagementHierarchy->name,
            'type' => $this->sourceManagementHierarchy->type,
//            'parent_id' => $this->sourceManagementHierarchy->parent_id,
//            "management"=>$this->sourceManagementHierarchy->parent?->type == "management" ? (new ManagementHierarchySimpleDataPresenter($this->sourceManagementHierarchy->parent))->getData() : null,
            "management" => $this->sourceManagementHierarchy->managementHierarchies()->first()?->parent?->type == "management" ? (new ManagementHierarchySimpleDataPresenter($this->sourceManagementHierarchy->managementHierarchies()->first()?->parent))->present() : null,

            'company_id' => $this->sourceManagementHierarchy->company_id,
            'is_active' => $this->sourceManagementHierarchy->is_active,
//            'is_main' => $this->sourceManagementHierarchy->is_main,
//            'manager' => $this->sourceManagementHierarchy->user ? (new UserPresenter($this->sourceManagementHierarchy->user))->present() : null,

//            'copies' => $this->sourceManagementHierarchy->clones?->map(function ($clone) {
//                return [
//                    'id' => $clone->id,
//                    'description' => $clone->description,
//                    'is_copied' => $clone->is_copied,
//                ];
//            })->toArray(),
            'users_count' => $this->sourceManagementHierarchy->managementHierarchies->sum(function ($clone) {
                return $clone->users_count ?? 0;
            })

        ];
    }
}
