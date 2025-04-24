<?php

namespace Modules\Auth\Handlers;

use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class LogoutHandler
{
    public function __construct(

    ) {
    }

    public function handle( )
    {
        JWTAuth::invalidate(JWTAuth::getToken());
    }
}
