<?php

namespace Modules\Company\ManagementHierarchy\Events;

use Illuminate\Queue\SerializesModels;
use Modules\Company\CompanyCore\Models\Company;

class CompanyCreatedEvent
{
    use SerializesModels;

    public Company $data;

    public function __construct(Company $data)
    {
        $this->data = $data;
    }
}
