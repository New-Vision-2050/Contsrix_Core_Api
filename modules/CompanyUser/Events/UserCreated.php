<?php

namespace Modules\CompanyUser\Events;

use Illuminate\Queue\SerializesModels;

class UserCreated
{
    use SerializesModels;
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }
}
