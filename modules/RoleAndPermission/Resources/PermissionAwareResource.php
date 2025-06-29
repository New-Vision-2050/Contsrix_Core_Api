<?php

namespace Modules\RoleAndPermission\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\RoleAndPermission\Services\PermissionService;

abstract class PermissionAwareResource extends JsonResource
{
    protected $permissionService;
    protected $resourcePrefix = null;

    public function __construct($resource)
    {
        parent::__construct($resource);
        $this->permissionService = app(PermissionService::class);
    }

    /**
     * Set the resource prefix for permission checks
     */
    public function setResourcePrefix(string $prefix): self
    {
        $this->resourcePrefix = $prefix;
        return $this;
    }

    /**
     * Transform the resource into an array with permission context
     */
    public function toArray($request)
    {
        $data = $this->transformData($request);
        
        if ($this->shouldIncludePermissionContext()) {
            $data['_permissions'] = $this->getPermissionContext($request);
        }

        return $data;
    }

    /**
     * Transform the core data - to be implemented by child classes
     */
    abstract protected function transformData($request): array;

    /**
     * Get permission context for the resource
     */
    protected function getPermissionContext($request): array
    {
        $user = $request->user();
        
        if (!$user || !$this->resourcePrefix) {
            return [];
        }

        return [
            'available_actions' => $this->getAvailableActions($user),
            'is_owner' => $this->isResourceOwner($user),
            'can_edit' => $this->canEdit($user),
            'can_delete' => $this->canDelete($user),
            'can_view_details' => $this->canViewDetails($user),
        ];
    }

    /**
     * Get available actions for the current user
     */
    protected function getAvailableActions($user): array
    {
        if (!$this->resourcePrefix) {
            return [];
        }

        $actions = config('permissions.actions', []);
        $availableActions = [];

        foreach ($actions as $actionKey => $actionConfig) {
            $permission = "{$this->resourcePrefix}.{$actionKey}";
            if ($this->permissionService->userHasPermission($user, $permission)) {
                $availableActions[$actionKey] = [
                    'label' => $actionConfig['label'] ?? ucwords($actionKey),
                    'description' => $actionConfig['description'] ?? null,
                    'color' => $actionConfig['color'] ?? '#6B7280',
                    'icon' => $actionConfig['icon'] ?? null,
                ];
            }
        }

        return $availableActions;
    }

    /**
     * Check if user is the owner of this resource
     */
    protected function isResourceOwner($user): bool
    {
        // Default implementation - override in child classes
        if (property_exists($this->resource, 'user_id')) {
            return $this->resource->user_id === $user->id;
        }

        if (property_exists($this->resource, 'created_by')) {
            return $this->resource->created_by === $user->id;
        }

        return false;
    }

    /**
     * Check if user can edit this resource
     */
    protected function canEdit($user): bool
    {
        if (!$this->resourcePrefix) {
            return false;
        }

        return $this->permissionService->userHasPermission($user, "{$this->resourcePrefix}.edit") ||
               $this->permissionService->userHasPermission($user, "{$this->resourcePrefix}.update");
    }

    /**
     * Check if user can delete this resource
     */
    protected function canDelete($user): bool
    {
        if (!$this->resourcePrefix) {
            return false;
        }

        return $this->permissionService->userHasPermission($user, "{$this->resourcePrefix}.delete");
    }

    /**
     * Check if user can view resource details
     */
    protected function canViewDetails($user): bool
    {
        if (!$this->resourcePrefix) {
            return true; // Default to true if no prefix set
        }

        return $this->permissionService->userHasPermission($user, "{$this->resourcePrefix}.view") ||
               $this->permissionService->userHasPermission($user, "{$this->resourcePrefix}.show");
    }

    /**
     * Determine if permission context should be included
     */
    protected function shouldIncludePermissionContext(): bool
    {
        return request()->get('include_permissions', true) && !is_null($this->resourcePrefix);
    }

    /**
     * Include field conditionally based on permission
     */
    protected function includeIfPermitted($value, string $permission, $default = null)
    {
        $user = request()->user();
        
        if (!$user) {
            return $default;
        }

        return $this->permissionService->userHasPermission($user, $permission) ? $value : $default;
    }

    /**
     * Filter array fields based on permissions
     */
    protected function filterByPermissions(array $data, array $permissionMap): array
    {
        $user = request()->user();
        
        if (!$user) {
            return [];
        }

        $filtered = [];
        
        foreach ($permissionMap as $key => $permission) {
            if ($this->permissionService->userHasPermission($user, $permission)) {
                $filtered[$key] = $data[$key] ?? null;
            }
        }

        return $filtered;
    }

    /**
     * Get masked/limited data based on permissions
     */
    protected function getMaskedValue($value, string $fullPermission, string $limitedPermission = null)
    {
        $user = request()->user();
        
        if (!$user) {
            return null;
        }

        // Full access
        if ($this->permissionService->userHasPermission($user, $fullPermission)) {
            return $value;
        }

        // Limited access
        if ($limitedPermission && $this->permissionService->userHasPermission($user, $limitedPermission)) {
            return $this->maskSensitiveData($value);
        }

        return null;
    }

    /**
     * Mask sensitive data for limited access
     */
    protected function maskSensitiveData($value)
    {
        if (is_string($value)) {
            return substr($value, 0, 3) . str_repeat('*', max(0, strlen($value) - 6)) . substr($value, -3);
        }

        return $value;
    }

    /**
     * Create collection with permission context
     */
    public static function collection($resource)
    {
        return tap(parent::collection($resource), function ($collection) {
            $collection->additional([
                '_meta' => [
                    'user_permissions' => request()->user() ? 
                        app(PermissionService::class)->getUserPermissionSummary(request()->user()) : [],
                ]
            ]);
        });
    }
}
