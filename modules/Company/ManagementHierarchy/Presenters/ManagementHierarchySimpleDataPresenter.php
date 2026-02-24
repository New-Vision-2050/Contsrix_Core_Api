<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Presenters;

use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\User\Models\User;

class ManagementHierarchySimpleDataPresenter extends AbstractPresenter
{
    private ManagementHierarchy $managementHierarchy;

    public function __construct(ManagementHierarchy $managementHierarchy)
    {
        $this->managementHierarchy = $managementHierarchy;
    }

    protected function present(bool $isListing = false): array
    {
        // Get users efficiently without n+1 query

        return [
            'id' => $this->managementHierarchy->id,
            "name"=>$this->managementHierarchy->name,
            'latitude' => $this->managementHierarchy->latitude,
            'longitude' => $this->managementHierarchy->longitude,
            'address' => $this->managementHierarchy->address,
            'type' => $this->managementHierarchy->type,
            "manager"=>$this->managementHierarchy->user,
        ];
    }
}
