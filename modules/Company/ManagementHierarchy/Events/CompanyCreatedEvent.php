<?php

namespace Modules\Company\ManagementHierarchy\Events;

use Illuminate\Queue\SerializesModels;

class CompanyCreatedEvent
{
    use SerializesModels;

    public function __construct()
    {
        //
    }
}
