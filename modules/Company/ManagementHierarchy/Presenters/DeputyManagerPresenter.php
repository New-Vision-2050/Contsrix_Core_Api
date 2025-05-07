<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Presenters;

use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchyDetailManager;

class DeputyManagerPresenter extends AbstractPresenter
{
    private ManagementHierarchyDetailManager $managementHierarchyDetailManager;

    public function __construct(ManagementHierarchyDetailManager $managementHierarchyDetailManager)
    {
        $this->managementHierarchyDetailManager = $managementHierarchyDetailManager;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->managementHierarchyDetailManager->user->id,
            'name' => $this->managementHierarchyDetailManager->user->name,
            'email' => $this->managementHierarchyDetailManager->user->email,

        ];
    }
}
