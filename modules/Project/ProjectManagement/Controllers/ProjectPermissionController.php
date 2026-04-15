<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Project\ProjectManagement\Services\ProjectPermissionService;
use Modules\Project\ProjectManagement\Presenters\ProjectPermissionLookupPresenter;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\Project\ProjectManagement\Models\ProjectRole;
use Modules\Project\ProjectManagement\Enums\ProjectPermission;
use Illuminate\Support\Facades\Cache;

class ProjectPermissionController extends Controller
{
    public function __construct(
        private ProjectPermissionService $service
    ) {
    }

    public function index(): JsonResponse
    {
        try {
            $permissions = $this->service->getAllPermissions();

            $data = $permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'submodule' => $permission->submodule,
                    'action' => $permission->action,
                    'title' => $permission->title,
                    'title_ar' => $permission->getTranslation('title', 'ar'),
                    'title_en' => $permission->getTranslation('title', 'en'),
                    'description' => $permission->description,
                    'is_active' => $permission->is_active,
                ];
            });

            return Json::items($data->toArray());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    public function getBySubmodule(Request $request): JsonResponse
    {
        try {
            $submodule = $request->route('submodule');

            if (!$submodule) {
                return Json::error('Submodule is required', 400);
            }

            $permissions = $this->service->getPermissionsBySubmodule($submodule);

            $data = $permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'submodule' => $permission->submodule,
                    'action' => $permission->action,
                    'title' => $permission->title,
                    'title_ar' => $permission->getTranslation('title', 'ar'),
                    'title_en' => $permission->getTranslation('title', 'en'),
                    'description' => $permission->description,
                ];
            });

            return Json::items($data->toArray());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $id = $request->route('id');

            $validated = $request->validate([
                'title_ar' => 'sometimes|string',
                'title_en' => 'sometimes|string',
                'description' => 'sometimes|string|nullable',
            ]);

            $updateData = [];

            if (isset($validated['title_ar']) || isset($validated['title_en'])) {
                $updateData['title'] = [];
                if (isset($validated['title_ar'])) {
                    $updateData['title']['ar'] = $validated['title_ar'];
                }
                if (isset($validated['title_en'])) {
                    $updateData['title']['en'] = $validated['title_en'];
                }
            }

            if (isset($validated['description'])) {
                $updateData['description'] = $validated['description'];
            }

            $permission = $this->service->updatePermission($id, $updateData);

            return Json::item($permission);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

    /**
     * Get permissions in hierarchical tree structure
     */
    public function getPermissionsTree(): JsonResponse
    {
        try {
            $permissions = $this->service->getAllPermissions();
            $presenter = new ProjectPermissionLookupPresenter();
            $tree = $presenter->present($permissions);

            return Json::item($tree);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

    /**
     * Get user's permissions for a specific project
     * Returns hierarchical structure of permissions based on user's role
     */
    public function getUserProjectPermissions(Request $request, string $projectId): JsonResponse
    {
        try {
            $userId = auth()->id();

            if (!$userId) {
                return Json::error('Unauthorized', 401);
            }

            // Get user's project employee record with role and permissions
            $projectEmployee = ProjectEmployee::where('project_id', $projectId)
                ->where('user_id', $userId)
                ->with(['projectRole.permissions'])
                ->first();

            if (!$projectEmployee) {
                return Json::error('User is not assigned to this project', 403);
            }

            if (!$projectEmployee->projectRole) {
                return Json::error('User has no role in this project', 403);
            }

            // Get user's permissions through their role
            $permissions = $projectEmployee->projectRole->permissions;

            // Present in hierarchical structure
            $presenter = new ProjectPermissionLookupPresenter();
            $tree = $presenter->present($permissions);

            return Json::success([
                'project_id' => $projectId,
                'user_id' => $userId,
                'role' => [
                    'id' => $projectEmployee->projectRole->id,
                    'name' => $projectEmployee->projectRole->name,
                    'slug' => $projectEmployee->projectRole->slug,
                ],
                'permissions' => $tree,
                'permissions_count' => $permissions->count(),
            ]);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

    /**
     * Get user's permissions for a specific project (flat format)
     */
    public function getUserProjectPermissionsFlat(Request $request, string $projectId): JsonResponse
    {
        try {
            $userId = auth()->id();

            if (!$userId) {
                return Json::error('Unauthorized', 401);
            }

            // Get user's project employee record with role and permissions
            $projectEmployee = ProjectEmployee::where('project_id', $projectId)
                ->where('user_id', $userId)
                ->with(['projectRole.permissions'])
                ->first();

            if (!$projectEmployee) {
                return Json::error('User is not assigned to this project', 403);
            }

            if (!$projectEmployee->projectRole) {
                return Json::error('User has no role in this project', 403);
            }

            // Get user's permissions through their role
            $permissions = $projectEmployee->projectRole->permissions;

            // Present in flat format
            $presenter = new ProjectPermissionLookupPresenter();
            $flat = $presenter->presentFlat($permissions);

            return Json::success([
                'project_id' => $projectId,
                'user_id' => $userId,
                'role' => [
                    'id' => $projectEmployee->projectRole->id,
                    'name' => $projectEmployee->projectRole->name,
                    'slug' => $projectEmployee->projectRole->slug,
                ],
                'permissions' => $flat,
                'permissions_count' => count($flat),
            ]);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

    /**
     * Check multiple permissions at once for current user
     * POST /projects/{project_id}/check-permissions
     * Body: { "permissions": ["PROJECT_EMPLOYEE_CREATE", "PROJECT_ARCHIVE_VIEW"] }
     */
    public function checkBulkPermissions(Request $request, string $projectId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'permissions' => 'required|array|min:1',
                'permissions.*' => 'required|string',
            ]);

            $userId = auth()->id();
            if (!$userId) {
                return Json::error('Unauthorized', 401);
            }

            // Get user's permissions (with caching)
            $cacheKey = "project.{$projectId}.user.{$userId}.permissions";
            $userPermissions = Cache::remember($cacheKey, 3600, function () use ($projectId, $userId) {
                $projectEmployee = ProjectEmployee::where('project_id', $projectId)
                    ->where('user_id', $userId)
                    ->with('projectRole.permissions')
                    ->first();

                return $projectEmployee?->projectRole?->permissions->pluck('name') ?? collect();
            });

            // Check each permission
            $results = [];
            foreach ($validated['permissions'] as $permission) {
                // Resolve config key to permission name
                $permissionName = ProjectPermission::get($permission) ?? $permission;
                $results[$permission] = $userPermissions->contains($permissionName);
            }

            return Json::success([
                'project_id' => $projectId,
                'user_id' => $userId,
                'permissions' => $results,
            ]);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

    /**
     * Get all users who have a specific permission in a project
     * GET /projects/{project_id}/users-with-permission/{permission_key}
     */
    public function getUsersWithPermission(string $projectId, string $permissionKey): JsonResponse
    {
        try {
            // Resolve permission key to name
            $permissionName = ProjectPermission::get($permissionKey) ?? $permissionKey;

            // Find all roles that have this permission
            $roles = ProjectRole::where('project_id', $projectId)
                ->whereHas('permissions', function ($query) use ($permissionName) {
                    $query->where('name', $permissionName);
                })
                ->with(['projectEmployees.user'])
                ->get();

            // Collect all unique users
            $users = collect();
            foreach ($roles as $role) {
                foreach ($role->projectEmployees as $employee) {
                    if ($employee->user) {
                        $users->push([
                            'id' => $employee->user->id,
                            'name' => $employee->user->name,
                            'email' => $employee->user->email,
                            'role' => [
                                'id' => $role->id,
                                'name' => $role->name,
                                'slug' => $role->slug,
                            ],
                        ]);
                    }
                }
            }

            return Json::success([
                'project_id' => $projectId,
                'permission' => $permissionKey,
                'permission_name' => $permissionName,
                'users' => $users->unique('id')->values(),
                'count' => $users->unique('id')->count(),
            ]);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

    /**
     * Compare permissions between two roles
     * GET /projects/{project_id}/roles/compare?role1={id}&role2={id}
     */
    public function compareRoles(Request $request, string $projectId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'role1' => 'required|string|exists:project_roles,id',
                'role2' => 'required|string|exists:project_roles,id',
            ]);

            $role1 = ProjectRole::with('permissions')->findOrFail($validated['role1']);
            $role2 = ProjectRole::with('permissions')->findOrFail($validated['role2']);

            // Verify both roles belong to the same project
            if ($role1->project_id !== $projectId || $role2->project_id !== $projectId) {
                return Json::error('Roles must belong to the specified project', 400);
            }

            $permissions1 = $role1->permissions->pluck('name');
            $permissions2 = $role2->permissions->pluck('name');

            // Find differences
            $onlyInRole1 = $permissions1->diff($permissions2)->values();
            $onlyInRole2 = $permissions2->diff($permissions1)->values();
            $common = $permissions1->intersect($permissions2)->values();

            return Json::success([
                'project_id' => $projectId,
                'role1' => [
                    'id' => $role1->id,
                    'name' => $role1->name,
                    'permissions_count' => $permissions1->count(),
                ],
                'role2' => [
                    'id' => $role2->id,
                    'name' => $role2->name,
                    'permissions_count' => $permissions2->count(),
                ],
                'comparison' => [
                    'common_permissions' => $common,
                    'common_count' => $common->count(),
                    'only_in_role1' => $onlyInRole1,
                    'only_in_role1_count' => $onlyInRole1->count(),
                    'only_in_role2' => $onlyInRole2,
                    'only_in_role2_count' => $onlyInRole2->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }
}
