<?php

namespace Modules\Auth\Handlers;

use Illuminate\Support\Facades\Auth;

class LogoutHandler
{
    public function __construct() {
    }

    public function handle()
    {
        Auth::guard('web')->logout();
    }
}
