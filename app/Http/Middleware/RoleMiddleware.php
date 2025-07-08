<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\RoleMiddleware as SpatieRoleMiddleware;

class RoleMiddleware extends SpatieRoleMiddleware
{
    /**
     * Handle an incoming request.
     * Check if the user has the specified role AND if that role is active (status = true)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|array  ...$roles
     * @return mixed
     */
    public function handle($request, Closure $next, ...$roles)
    {
        // Set company ID for multi-tenant environments
        if (!empty(auth('api')->user())) {
            setPermissionsTeamId(auth('api')->user()->company_id);
        }

        $authGuard = config('auth.defaults.guard');

        if (auth()->guard($authGuard)->guest()) {
            throw UnauthorizedException::notLoggedIn();
        }

        $roles = is_array($roles[0]) ? $roles[0] : $roles;

        $user = auth()->guard($authGuard)->user();

        $userRoles = $user->roles()->whereIn('name', $roles)->get();

        if ($userRoles->where('status', true)->isEmpty()) {
            throw UnauthorizedException::forRoles($roles);
        }

        return parent::handle($request, $next, ...$roles);
    }
}
