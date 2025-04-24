<?php

namespace Modules\CompanyUser\Events;

use Illuminate\Queue\SerializesModels;

class UserUpdated
{
    use SerializesModels;
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }
}
