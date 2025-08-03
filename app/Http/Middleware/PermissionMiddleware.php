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
     * @param  string|array  $permission
     * @return mixed
     */
/**
* Handle an incoming request.
* Check if the user has the specified permission AND if that permission is active (status = true)
*
* @param  \Illuminate\Http\Request  $request
* @param  \Closure  $next
* @param  string|array  $permission
* @return mixed
*/
    public function handle($request, Closure $next, $permission, $guard = null)
    {
        // Set company ID for multi-tenant environments
        if (!empty(auth('api')->user())) {
            setPermissionsTeamId(auth('api')->user()->company_id);
        }

        $authGuard = config('auth.defaults.guard');

        if (auth()->guard($authGuard)->guest()) {
            throw UnauthorizedException::notLoggedIn();
        }

        if(auth()->check() && auth()->user()->email == 'admin@constrix-nv.com') {
            return $next($request);
        }
        if(auth()->check() && auth()->user()->hasRole('super-admin') && tenant("is_central_company")) {
            return $next($request);
        }

        $permissions = explode('|', $permission);

        $user = auth()->guard($authGuard)->user();

        // Check if user has any of the permissions (OR logic)


        $activePermissions = [];
        foreach ($permissions as $perm) {
            $permissionModel = $this->permissionRepository->findByName($perm);
            if ($permissionModel && $permissionModel->status) {
                $activePermissions[] = $perm;
            }
        }

// Check if user has any of the ACTIVE permissions (OR logic)
        if (empty($activePermissions) || ! $user->canAny($activePermissions)) {
            throw UnauthorizedException::forPermissions($permissions);
        }

        if (! $user->canAny($activePermissions)) {
            throw UnauthorizedException::forPermissions($permissions);
        }

        // Additional checks for active permissions and limits
        foreach ($permissions as $perm) {
            if ($user->hasPermissionTo($perm, $authGuard)) {
                $permissionModel = $this->permissionRepository->findByName($perm);


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
                            "Permission '{$perm}' limit exceeded. No more usage allowed."
                        );
                    }

                    // Decrease the actual limit (consume one usage)
                    $permissionLimit->decreaseLimit();
                }

                // Break after first valid permission found (since we only need ANY permission)
                break;
            }
        }

        return $next($request);
    }}
