<?php

namespace Modules\Auth\Middleware;

use BasePackage\Shared\Facade\Json;
use Closure;
use Illuminate\Http\Request;

class ContinueWithOtp
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        if ((int)config("app.continue_with_otp") == 1)
            return $next($request);
        return Json::buildItems(null, ["msg" => __("validation.invalid-to-login-with-otp")], "", 403);
    }
}
