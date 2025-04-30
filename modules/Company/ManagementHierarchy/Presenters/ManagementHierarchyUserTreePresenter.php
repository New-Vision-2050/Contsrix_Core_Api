<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Presenters;

use App\Traits\CalculateTreeManagementHierarchy;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\User\Models\User;
use Modules\User\Presenters\UserPresenter;

class ManagementHierarchyUserTreePresenter extends AbstractPresenter
{
    use CalculateTreeManagementHierarchy;

    private ManagementHierarchy $managementHierarchy;

    public function __construct(ManagementHierarchy $managementHierarchy)
    {
        $this->managementHierarchy = $managementHierarchy;
    }


    protected function present(bool $isListing = false): array
    {

        return  [
            "users"=>UserPresenter::collection($this->managementHierarchy->directUserChildren),
            "children" => ManagementHierarchyUserTreePresenter::collection($this->managementHierarchy->children),

        ];
    }
}
