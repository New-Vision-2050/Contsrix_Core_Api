<?php

namespace App\Http\Middleware;

use Closure;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware as SpatiePermissionMiddleware;
use Modules\RoleAndPermission\Models\Permission;
use Modules\Subscription\Package\Models\CompanyPermissionLimit;
use Modules\RoleAndPermission\Repositories\PermissionRepository;
use Modules\Subscription\Package\Repositories\CompanyPermissionLimitRepository;

class PermissionMiddleware extends SpatiePermissionMiddleware
{
    public function __construct(
        private PermissionRepository $permissionRepository,
        private CompanyPermissionLimitRepository $companyPermissionLimitRepository
    ) {}

    /**
     * Handle an incoming request.
     * Check if the user has the specified permission AND if that permission is active (status = true)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|array  ...$permissions
     * @return mixed
     */
    public function handle($request, Closure $next, ...$permissions)
    {
        // Set company ID for multi-tenant environments
        if (!empty(auth('api')->user())) {
            setPermissionsTeamId(auth('api')->user()->company_id);
        }

        $authGuard = config('auth.defaults.guard');

        if (auth()->guard($authGuard)->guest()) {
            throw UnauthorizedException::notLoggedIn();
        }
//        if(auth()->check() &&( auth()->user()->is_owner == 1|| auth()->user()->email == 'admin@constrix-nv.com')) {
//
//            return $next($request);
//        }

        $permissions = is_array($permissions[0]) ? $permissions[0] : $permissions;

        $user = auth()->guard($authGuard)->user();


//        if($user->hasRole('super-admin') || $user->hasRole('admin')) {
//            return $next($request);
//        }

        foreach ($permissions as $permission) {
            if ($user->hasPermissionTo($permission, $authGuard)) {
                $permissionModel = $this->permissionRepository->findByName($permission);

                if (!$permissionModel || !$permissionModel->status) {
                    throw UnauthorizedException::forPermissions($permissions);
                }

                // Check if this permission has a limit for the company
                $permissionLimit = $this->companyPermissionLimitRepository->findByCompanyAndPermission(
                    $user->company_id,
                    $permissionModel->id
                );

                if ($permissionLimit) {
                    // Check if limit is exceeded
                    if ($permissionLimit->isLimitExceeded()) {
                        throw new UnauthorizedException(
                            403,
                            "Permission '{$permission}' limit exceeded. No more usage allowed."
                        );
                    }

                    // Decrease the actual limit (consume one usage)
                    $permissionLimit->decreaseLimit();
                }
            } else {
                throw UnauthorizedException::forPermissions($permissions);
            }
        }

        return $next($request);
    }
}
