<?php

namespace Modules\RoleAndPermission\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Modules\RoleAndPermission\Services\PermissionService;
use Modules\RoleAndPermission\Exceptions\UnauthorizedException;

class AdvancedPermissionMiddleware
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Handle permission verification with advanced features
     */
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        $user = $request->user();
        
        if (!$user) {
            throw new UnauthorizedException('Authentication required');
        }

        // Log permission check for audit
        $this->logPermissionCheck($user, $permissions, $request);

        // Check if user has required permissions
        if (!$this->hasPermissions($user, $permissions)) {
            // Log unauthorized access attempt
            Log::warning('Unauthorized access attempt', [
                'user_id' => $user->id,
                'required_permissions' => $permissions,
                'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                'route' => $request->route()->getName(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            throw new UnauthorizedException('Insufficient permissions');
        }

        // Check for critical permissions
        $this->checkCriticalPermissions($permissions, $request);

        // Rate limiting for sensitive operations
        $this->applySensitiveOperationRateLimit($user, $permissions, $request);

        return $next($request);
    }

    /**
     * Check if user has required permissions
     */
    protected function hasPermissions($user, array $permissions): bool
    {
        // Use caching for permission checks
        $cacheKey = "user_permissions_{$user->id}";
        $userPermissions = Cache::remember($cacheKey, 300, function () use ($user) {
            return $user->getAllPermissions()->pluck('name')->toArray();
        });

        // Support for OR logic (any permission matches)
        if (in_array('OR', $permissions)) {
            $permissionsToCheck = array_diff($permissions, ['OR']);
            foreach ($permissionsToCheck as $permission) {
                if (in_array($permission, $userPermissions)) {
                    return true;
                }
            }
            return false;
        }

        // Support for wildcard permissions
        foreach ($permissions as $permission) {
            if (!$this->checkWildcardPermission($permission, $userPermissions)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Support wildcard permissions (e.g., users.*, users.user.*)
     */
    protected function checkWildcardPermission(string $requiredPermission, array $userPermissions): bool
    {
        // Direct match
        if (in_array($requiredPermission, $userPermissions)) {
            return true;
        }

        // Wildcard match
        foreach ($userPermissions as $userPermission) {
            if (str_ends_with($userPermission, '*')) {
                $prefix = rtrim($userPermission, '*');
                if (str_starts_with($requiredPermission, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check for critical permissions that require additional verification
     */
    protected function checkCriticalPermissions(array $permissions, Request $request): void
    {
        $criticalPermissions = config('permissions.critical_permissions', []);
        $requestedCritical = array_intersect($permissions, $criticalPermissions);

        if (!empty($requestedCritical)) {
            // Log critical permission usage
            Log::info('Critical permission accessed', [
                'user_id' => $request->user()->id,
                'permissions' => $requestedCritical,
                'route' => $request->route()->getName(),
                'ip' => $request->ip(),
                'timestamp' => now(),
            ]);

            // Additional verification for critical operations
            $this->verifyCriticalPermissionContext($requestedCritical, $request);
        }
    }

    /**
     * Apply rate limiting for sensitive operations
     */
    protected function applySensitiveOperationRateLimit($user, array $permissions, Request $request): void
    {
        $sensitiveActions = ['delete', 'bulk-delete', 'login-as-admin'];
        $hasSensitivePermission = false;

        foreach ($permissions as $permission) {
            foreach ($sensitiveActions as $action) {
                if (str_contains(strtolower($permission), $action)) {
                    $hasSensitivePermission = true;
                    break 2;
                }
            }
        }

        if ($hasSensitivePermission) {
            $key = "sensitive_ops_{$user->id}";
            $attempts = Cache::get($key, 0);

            if ($attempts >= 10) { // Max 10 sensitive operations per hour
                throw new UnauthorizedException('Rate limit exceeded for sensitive operations');
            }

            Cache::put($key, $attempts + 1, 3600); // 1 hour
        }
    }

    /**
     * Additional verification for critical permissions
     */
    protected function verifyCriticalPermissionContext(array $criticalPermissions, Request $request): void
    {
        // Check for additional context requirements
        foreach ($criticalPermissions as $permission) {
            switch ($permission) {
                case 'COMPANY_LOGIN_AS_ADMIN':
                    // Require special admin session or 2FA verification
                    if (!$request->session()->has('admin_verified_at') || 
                        $request->session()->get('admin_verified_at') < now()->subMinutes(30)) {
                        throw new UnauthorizedException('Admin verification required for this action');
                    }
                    break;

                case 'USER_BULK_DELETE':
                case 'COMPANY_BULK_DELETE':
                    // Require confirmation token for bulk operations
                    if (!$request->has('confirmation_token') || 
                        !$this->verifyBulkOperationToken($request->get('confirmation_token'))) {
                        throw new UnauthorizedException('Bulk operation confirmation required');
                    }
                    break;
            }
        }
    }

    /**
     * Verify bulk operation confirmation token
     */
    protected function verifyBulkOperationToken(string $token): bool
    {
        return Cache::get("bulk_operation_token_{$token}") === true;
    }

    /**
     * Log permission checks for audit trail
     */
    protected function logPermissionCheck($user, array $permissions, Request $request): void
    {
        if (config('app.debug') || config('permissions.log_checks', false)) {
            Log::debug('Permission check', [
                'user_id' => $user->id,
                'permissions' => $permissions,
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'path' => $request->path(),
            ]);
        }
    }
}
