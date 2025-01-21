<?php

namespace Modules\Auth\Services\Interfaces;

use Modules\User\Models\User;

interface SendOtp
{
    public function send();
}
