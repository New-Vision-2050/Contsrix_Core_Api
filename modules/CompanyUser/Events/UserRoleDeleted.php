<?php

namespace Modules\CompanyUser\Events;

use Illuminate\Queue\SerializesModels;

class UserRoleDeleted
{
    use SerializesModels;
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }
}
