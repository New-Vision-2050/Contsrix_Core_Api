<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Presenters;

use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ManagementHierarchyPresenter extends AbstractPresenter
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
            'parent_id' => $this->managementHierarchy->parent_id,
            'name' => $this->managementHierarchy->name,
            'phone' => $this->managementHierarchy->phone,
            'phone_code' => $this->managementHierarchy->phone_code,
            'email' => $this->managementHierarchy->email,
            'lattitude' => $this->managementHierarchy->lattitude,
            'longitude' => $this->managementHierarchy->longitude,
            'country_id' => $this->managementHierarchy->country_id,

            //example of nested structure
//            'user' => $this->managementHierarchy->users,
        ];
    }
}
