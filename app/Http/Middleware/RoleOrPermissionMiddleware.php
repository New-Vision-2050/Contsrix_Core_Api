<?php

namespace App\Http\Middleware;

use Closure;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware as SpatieRoleOrPermissionMiddleware;
use Modules\RoleAndPermission\Models\Permission;
use Modules\RoleAndPermission\Models\Role;

class RoleOrPermissionMiddleware extends SpatieRoleOrPermissionMiddleware
{
    /**
     * Handle an incoming request.
     * Check if user has any of the given permissions OR roles AND that they are active (status = true)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|array  $roleOrPermission
     * @return mixed
     */
    public function handle($request, Closure $next, ...$roleOrPermission)
    {
        // Set company ID for multi-tenant environments
        if (!empty(auth('api')->user())) {
            setPermissionsTeamId(auth('api')->user()->company_id);
        }

        $authGuard = config('auth.defaults.guard');

        if (is_string($roleOrPermission)) {
            $roleOrPermission = explode('|', $roleOrPermission);
        }

        if ($authGuard->guest()) {
            throw UnauthorizedException::notLoggedIn();
        }

        $roleOrPermission = collect($roleOrPermission);
        $user = $authGuard->user();
        $companyId = $user->company_id;
        if($user->hasRole('super-admin') || $user->hasRole('admin')||(auth()->check() || auth()->user()->is_owner == 1)) {
            return $next($request);
        }

        // Get all permissions the user has that are in the list
        $userPermissions = $user->getPermissionNames();
        $permissionsToCheck = $roleOrPermission->intersect($userPermissions);

        if ($permissionsToCheck->isNotEmpty()) {
            // Check if any of these permissions are active
            $hasActivePermission = Permission::whereIn('name', $permissionsToCheck)
                ->where('company_id', $companyId)
                ->where('status', true)
                ->exists();

            if ($hasActivePermission) {
                return $next($request);
            }
        }

        // Get all roles the user has that are in the list
        $userRoles = $user->getRoleNames();
        $rolesToCheck = $roleOrPermission->intersect($userRoles);

        if ($rolesToCheck->isNotEmpty()) {
            // Check if any of these roles are active
            $hasActiveRole = Role::whereIn('name', $rolesToCheck)
                ->where('status', true)
                ->exists();

            if ($hasActiveRole) {
                return $next($request);
            }
        }

        throw UnauthorizedException::forRolesOrPermissions($roleOrPermission->toArray());
    }
}
