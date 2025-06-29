<?php

namespace Modules\RoleAndPermission\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\RoleAndPermission\Services\PermissionService;
use Modules\RoleAndPermission\Exceptions\UnauthorizedException;

trait HasPermissionValidation
{
    /**
     * Check if authenticated user has permission
     */
    protected function checkPermission(string $permission, Request $request = null): bool
    {
        $user = $request ? $request->user() : auth()->user();
        
        if (!$user) {
            return false;
        }

        return app(PermissionService::class)->userHasPermission($user, $permission);
    }

    /**
     * Check multiple permissions with AND/OR logic
     */
    protected function checkPermissions(array $permissions, string $logic = 'AND', Request $request = null): bool
    {
        $user = $request ? $request->user() : auth()->user();
        
        if (!$user) {
            return false;
        }

        return app(PermissionService::class)->userHasPermissions($user, $permissions, $logic);
    }

    /**
     * Authorize permission or throw exception
     */
    protected function authorizePermission(string $permission, Request $request = null): void
    {
        if (!$this->checkPermission($permission, $request)) {
            throw new UnauthorizedException("Missing required permission: {$permission}");
        }
    }

    /**
     * Authorize multiple permissions or throw exception
     */
    protected function authorizePermissions(array $permissions, string $logic = 'AND', Request $request = null): void
    {
        if (!$this->checkPermissions($permissions, $logic, $request)) {
            $permissionsList = implode(', ', $permissions);
            throw new UnauthorizedException("Missing required permissions ({$logic}): {$permissionsList}");
        }
    }

    /**
     * Return permission-filtered response
     */
    protected function permissionFilteredResponse($data, array $permissionMap, Request $request = null): array
    {
        $user = $request ? $request->user() : auth()->user();
        $permissionService = app(PermissionService::class);
        
        if (!$user) {
            return [];
        }

        $filtered = [];
        
        foreach ($permissionMap as $key => $permission) {
            if ($permissionService->userHasPermission($user, $permission)) {
                $filtered[$key] = $data[$key] ?? null;
            }
        }

        return $filtered;
    }

    /**
     * Get user's available actions for a resource
     */
    protected function getUserAvailableActions(string $resourcePrefix, Request $request = null): array
    {
        $user = $request ? $request->user() : auth()->user();
        $permissionService = app(PermissionService::class);
        
        if (!$user) {
            return [];
        }

        $actions = config('permissions.actions', []);
        $availableActions = [];

        foreach ($actions as $actionKey => $actionConfig) {
            $permission = "{$resourcePrefix}.{$actionKey}";
            if ($permissionService->userHasPermission($user, $permission)) {
                $availableActions[$actionKey] = $actionConfig;
            }
        }

        return $availableActions;
    }

    /**
     * Filter collection based on permissions
     */
    protected function filterCollectionByPermissions($collection, string $permission, Request $request = null)
    {
        if (!$this->checkPermission($permission, $request)) {
            return collect();
        }

        return $collection;
    }

    /**
     * Add permission context to API response
     */
    protected function addPermissionContext(array $response, string $resourcePrefix, Request $request = null): array
    {
        $response['_permissions'] = [
            'available_actions' => $this->getUserAvailableActions($resourcePrefix, $request),
            'is_super_admin' => app(PermissionService::class)->isSuperAdmin($request ? $request->user() : auth()->user()),
        ];

        return $response;
    }

    /**
     * Create permission-aware JSON response
     */
    protected function permissionAwareResponse(array $data, string $resourcePrefix = null, int $status = 200): JsonResponse
    {
        if ($resourcePrefix) {
            $data = $this->addPermissionContext($data, $resourcePrefix);
        }

        return response()->json($data, $status);
    }

    /**
     * Conditional data inclusion based on permissions
     */
    protected function includeIfPermitted($data, string $permission, $default = null, Request $request = null)
    {
        return $this->checkPermission($permission, $request) ? $data : $default;
    }

    /**
     * Get paginated results with permission filtering
     */
    protected function getPaginatedWithPermissions($query, string $viewPermission, Request $request, int $perPage = 15)
    {
        // Check if user has permission to view the resource
        $this->authorizePermission($viewPermission, $request);

        // Apply additional filters based on user permissions
        $user = $request->user();
        $permissionService = app(PermissionService::class);

        // If user is not super admin, apply additional filters
        if (!$permissionService->isSuperAdmin($user)) {
            // Add any user-specific filtering logic here
            // For example, only show resources belonging to user's company
            if (method_exists($query->getModel(), 'company')) {
                $query->whereHas('company', function ($q) use ($user) {
                    $q->where('id', $user->company_id);
                });
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Log permission usage for audit
     */
    protected function logPermissionUsage(string $permission, string $action, Request $request = null): void
    {
        $user = $request ? $request->user() : auth()->user();
        
        if (config('permissions.log_usage', false)) {
            logger()->info('Permission used', [
                'user_id' => $user?->id,
                'permission' => $permission,
                'action' => $action,
                'ip' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'timestamp' => now(),
            ]);
        }
    }
}
