<?php

namespace Modules\Auth\Middleware;

use App\Models\Setting;
use BasePackage\Shared\Presenters\Json;
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

        if ((int)Setting::where("key","continue_with_otp")->first()->value == 1)
            return $next($request);
        return Json::buildItems(null, ["msg" => __("validation.invalid-to-login-with-otp")], "", 403);
    }
}
