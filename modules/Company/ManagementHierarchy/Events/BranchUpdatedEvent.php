<?php

namespace Modules\Company\ManagementHierarchy\Events;

use Illuminate\Queue\SerializesModels;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;

class BranchUpdatedEvent
{
    use SerializesModels;

    public ManagementHierarchy $managementHierarchy;

    public function __construct(ManagementHierarchy $managementHierarchy)
    {
        $this->managementHierarchy = $managementHierarchy;
    }
}
