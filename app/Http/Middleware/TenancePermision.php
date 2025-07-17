<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenancePermision
{


    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth('api')->check()) {
            $user = auth('api')->user();
            if ($user && $user->company_id) {
                setPermissionsTeamId($user->company_id);
            }
        }
        return $next($request);
    }
}
