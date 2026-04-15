<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\Project\ProjectManagement\Enums\ProjectPermission;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Support\Facades\Cache;

class CheckProjectPermission
{
    /**
     * Handle project permission check
     * 
     * Accepts either:
     * 1. Config key (e.g., 'PROJECT_EMPLOYEE_CREATE')
     * 2. Permission name (e.g., 'project-management.project-management*employee.create')
     * 
     * @param Request $request
     * @param Closure $next
     * @param string $permission Config key or permission name
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        $projectId = $request->route('project_id') ?? $request->input('project_id');
        $userId = auth()->id();

        if (!$projectId || !$userId) {
            return response()->json(Json::error('Unauthorized', 403)->getData(), 403);
        }

        // Resolve permission: if it's a config key, get the actual permission name
        $permissionName = $this->resolvePermission($permission);

        // Get user's permissions with caching (1 hour cache)
        $cacheKey = "project.{$projectId}.user.{$userId}.permissions";
        $userPermissions = Cache::remember($cacheKey, 3600, function () use ($projectId, $userId) {
            $projectEmployee = ProjectEmployee::where('project_id', $projectId)
                ->where('user_id', $userId)
                ->with('projectRole.permissions')
                ->first();

            if (!$projectEmployee || !$projectEmployee->projectRole) {
                return null;
            }

            return $projectEmployee->projectRole->permissions->pluck('name');
        });

        // Check if user is assigned to project
        if ($userPermissions === null) {
            return response()->json(
                Json::error('You are not assigned to this project', 403)->getData(),
                403
            );
        }

        $hasPermission = $userPermissions->contains($permissionName);

        if (!$hasPermission) {
            return response()->json(
                Json::error("You don't have permission: {$permissionName}", 403)->getData(),
                403
            );
        }

        return $next($request);
    }

    /**
     * Resolve permission from config key or return as-is if it's already a permission name
     * 
     * @param string $permission
     * @return string
     */
    private function resolvePermission(string $permission): string
    {
        // If it looks like a config key (all uppercase with underscores)
        if (preg_match('/^[A-Z_]+$/', $permission)) {
            try {
                return ProjectPermission::get($permission) ?? $permission;
            } catch (\Exception $e) {
                // If config key not found, treat as permission name
                return $permission;
            }
        }

        // Otherwise, treat as permission name
        return $permission;
    }
}
