<?php

namespace App\Http\Middleware;

use Closure;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware as SpatiePermissionMiddleware;
use Modules\RoleAndPermission\Models\Permission;
use Modules\Subscription\Package\Models\CompanyPermissionLimit;
use Modules\RoleAndPermission\Repositories\PermissionRepository;
use Modules\Subscription\Package\Repositories\CompanyPermissionLimitRepository;
use Modules\ArchiveLibrary\File\Models\File;

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


            // Check if this permission has a limit for the company
            $permissionLimit = $this->companyPermissionLimitRepository->findByCompanyAndPermission(
                $user->company_id,
                $permissionModel->id
            );

            // Determine operation type based on permission name and HTTP method
            $isCreateOperation = str_contains(strtolower($perm), 'create') || $request->isMethod('POST');
            $isDeleteOperation = str_contains(strtolower($perm), 'delete') || $request->isMethod('DELETE');
            $isUpdateOperation = str_contains(strtolower($perm), 'update') || $request->isMethod('PUT') || $request->isMethod('PATCH');

            // Check if this is a file-related permission (uses size-based limits)
            $isFilePermission = str_contains(strtolower($perm), 'archive-library*file');

            if ($isCreateOperation && $permissionLimit) {
                // Check if limit is exceeded for CREATE operations
                if ($permissionLimit->isLimitExceeded()) {
                    throw new UnauthorizedException(
                        403,
                        "Permission '{$perm}' limit exceeded. No more usage allowed."
                    );
                }

                // For file permissions, decrease by file size; otherwise by count
                if ($isFilePermission) {
                    // Get file size from request (in MB or KB, convert to your unit)
                    $fileSize = $this->getFileSizeFromRequest($request);

                    if ($fileSize > 0) {
                        // Check if file size exceeds remaining limit
                        if ($permissionLimit->actual_limit < $fileSize) {
                            throw new UnauthorizedException(
                                403,
                                "File size ({$fileSize} MB) exceeds remaining storage limit ({$permissionLimit->actual_limit} MB)."
                            );
                        }
                        $permissionLimit->decreaseLimit($fileSize);
                    }
                } else {
                    // Decrease by count (1) for non-file permissions
                    $permissionLimit->decreaseLimit();
                }
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
                        // For file permissions, restore by file size; otherwise by count
                        if ($isFilePermission) {
                            // Get file size from database for DELETE operations
                            $fileSize = $this->getOldFileSizeFromDatabase($request);
                            $createPermissionLimit->increaseLimit($fileSize > 0 ? $fileSize : 1);
                        } else {
                            // Increase by count (1) for non-file permissions
                            $createPermissionLimit->increaseLimit();
                        }
                    }
                }
            } elseif ($isUpdateOperation && $isFilePermission) {
                // Handle UPDATE operations where file is being replaced (optional)
                $createPermissionName = str_replace('.update', '.create', $perm);
                $createPermission = $this->permissionRepository->findByName($createPermissionName);

                if ($createPermission) {
                    $createPermissionLimit = $this->companyPermissionLimitRepository->findByCompanyAndPermission(
                        $user->company_id,
                        $createPermission->id
                    );

                    if ($createPermissionLimit) {
                        // Check if a new file is being uploaded (optional)
                        $newFileSize = $this->getFileSizeFromRequest($request);

                        if ($newFileSize > 0) {
                            // File is being replaced
                            // Get old file size from database using file ID from URL
                            $oldFileSize = $this->getOldFileSizeFromDatabase($request);

                            // Calculate size difference
                            $sizeDifference = $newFileSize - $oldFileSize;

                            if ($sizeDifference > 0) {
                                // New file is larger - need more storage
                                if ($createPermissionLimit->actual_limit < $sizeDifference) {
                                    throw new UnauthorizedException(
                                        403,
                                        "Insufficient storage. Need {$sizeDifference} MB more (new: {$newFileSize} MB, old: {$oldFileSize} MB)."
                                    );
                                }
                                $createPermissionLimit->decreaseLimit($sizeDifference);
                            } elseif ($sizeDifference < 0) {
                                // New file is smaller - free up storage
                                $createPermissionLimit->increaseLimit(abs($sizeDifference));
                            }
                            // If sizeDifference == 0, no change needed (same size files)
                        }
                        // If no new file uploaded, no limit changes (just updating metadata)
                    }
                }
            }
            // For other operations (VIEW, LIST, EXPORT), don't modify limits

            // Break after first valid permission found (since we only need ANY permission)
            break;

        }

        return $next($request);
    }

    /**
     * Get file size from request (in MB)
     * Handles both file uploads and file size parameters
     *
     * @param \Illuminate\Http\Request $request
     * @return int File size in MB
     */
    private function getFileSizeFromRequest($request): int
    {
        // Check if request has a file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            // Get file size in bytes and convert to MB
            $sizeInBytes = $file->getSize();
            return (int)ceil($sizeInBytes / (1024 * 1024)); // Convert bytes to MB
        }

        // Check if file size is provided in request data (for delete operations)
        if ($request->has('file_size')) {
            return (int)$request->input('file_size');
        }

        // Check for size in MB parameter
        if ($request->has('size')) {
            return (int)$request->input('size');
        }

        return 0;
    }

    /**
     * Get old file size from database using file ID from URL
     *
     * @param \Illuminate\Http\Request $request
     * @return int File size in MB
     */
    private function getOldFileSizeFromDatabase($request): int
    {
        try {
            // Get file ID from route parameter
            $fileId = $request->route('id') ?? $request->route('file');

            if (!$fileId) {
                return 0;
            }

            // Fetch the file from database
            $file = File::find($fileId);

            if (!$file) {
                return 0;
            }

            // Get the first media file
            $media = $file->getFirstMedia();

            if (!$media || !$media->size) {
                return 0;
            }

            // Convert bytes to MB
            return (int)ceil($media->size / (1024 * 1024));

        } catch (\Exception $e) {
            \Log::warning('Failed to get old file size from database', [
                'error' => $e->getMessage(),
                'file_id' => $fileId ?? 'unknown'
            ]);
            return 0;
        }
    }
}
