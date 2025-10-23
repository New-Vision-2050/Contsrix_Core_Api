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
        private PermissionRepository             $permissionRepository,
        private CompanyPermissionLimitRepository $companyPermissionLimitRepository
    )
    {
    }

    /**
     * Handle an incoming request.
     * Check if the user has the specified permission AND if that permission is active (status = true)
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string|array $permission
     * @return mixed
     */
    /**
     * Handle an incoming request.
     * Check if the user has the specified permission AND if that permission is active (status = true)
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string|array $permission
     * @return mixed
     */
    public function handle($request, Closure $next, $permission, $guard = null)
    {
        // Set company ID for multi-tenant environments
        if (!empty(auth('api')->user())) {
            setPermissionsTeamId(auth('api')->user()->company_id);
        }

        $authGuard = config('auth.defaults.guard');
        $isSuperAdmin = 0;

        if (auth()->guard($authGuard)->guest()) {
            throw UnauthorizedException::notLoggedIn();
        }

        if (auth()->check() && auth()->user()->email == 'admin@constrix-nv.com') {
            $isSuperAdmin = 1;
        }
        if (auth()->check() && auth()->user()->hasRole('super-admin') && tenant("is_central_company")) {
            $isSuperAdmin = 1;
        }

        $permissions = explode('|', $permission);

        $user = auth()->guard($authGuard)->user();

        // Check if user has any of the permissions (OR logic)

        if (!$isSuperAdmin) {
            $activePermissions = [];
            foreach ($permissions as $perm) {
                $permissionModel = $this->permissionRepository->findByName($perm);
                if ($permissionModel && $permissionModel->status) {
                    $activePermissions[] = $perm;
                }
            }

// Check if user has any of the ACTIVE permissions (OR logic)
            if (empty($activePermissions) || !$user->canAny($activePermissions)) {
                throw UnauthorizedException::forPermissions($permissions);
            }

            if (!$user->canAny($activePermissions)) {
                throw UnauthorizedException::forPermissions($permissions);
            }
        }


        // Additional checks for active permissions and limits
        foreach ($permissions as $perm) {
            $permissionModel = $this->permissionRepository->findByName($perm);


            // Skip file permissions - they are handled by FileObserver
            // FileObserver automatically tracks storage limits for all file operations
            $isFilePermission = str_contains(strtolower($perm), 'archive-library*file');
            
            if ($isFilePermission) {
                // Do nothing - FileObserver handles all file limit logic
                // This includes create, update, delete operations
                // No error throwing, no limit checking, no limit adjustment
                break;
            }

            // Check if this is a folder-related permission (count-based limits)
            $isFolderPermission = str_contains(strtolower($perm), 'archive-library*folder');
            
            // Only process folder permissions (not file permissions)
            if ($isFolderPermission) {
                // Check if this permission has a limit for the company
                $permissionLimit = $this->companyPermissionLimitRepository->findByCompanyAndPermission(
                    $user->company_id,
                    $permissionModel->id
                );

                // Determine operation type based on permission name and HTTP method
                $isCreateOperation = str_contains(strtolower($perm), 'create') || $request->isMethod('POST');
                $isDeleteOperation = str_contains(strtolower($perm), 'delete') || $request->isMethod('DELETE');

                if ($isCreateOperation && $permissionLimit) {
                    // Check if limit is exceeded for CREATE operations
                    if ($permissionLimit->isLimitExceeded()) {
                        throw new UnauthorizedException(
                            403,
                            "Permission '{$perm}' limit exceeded. No more usage allowed."
                        );
                    }

                    // Decrease by count (1) for folder creation
                    $permissionLimit->decreaseLimit();
                    
                } elseif ($isDeleteOperation) {
                    // Find the corresponding CREATE permission to restore its limit
                    $createPermissionName = str_replace('.delete', '.create', $perm);
                    $createPermission = $this->permissionRepository->findByName($createPermissionName);

                    if ($createPermission) {
                        $createPermissionLimit = $this->companyPermissionLimitRepository->findByCompanyAndPermission(
                            $user->company_id,
                            $createPermission->id
                        );

                        if ($createPermissionLimit) {
                            // Increase by count (1) for folder deletion
                            $createPermissionLimit->increaseLimit();
                        }
                    }
                }
            }
            // For other operations (VIEW, UPDATE, LIST, EXPORT), don't modify limits

            // Break after first valid permission found (since we only need ANY permission)
            break;

        }

        return $next($request);
    }
}
