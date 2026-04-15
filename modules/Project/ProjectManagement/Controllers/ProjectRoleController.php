<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Project\ProjectManagement\Services\ProjectRoleService;
use Modules\Project\ProjectManagement\Presenters\ProjectPermissionLookupPresenter;

class ProjectRoleController extends Controller
{
    public function __construct(
        private ProjectRoleService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $projectId = $request->route('project_id');

            if (!$projectId) {
                return Json::error('Project ID is required', 400);
            }

            $roles = $this->service->getProjectRoles($projectId);
            $presenter = new ProjectPermissionLookupPresenter();

            $data = $roles->map(function ($role) use ($presenter) {
                $permissionsTree = $presenter->present($role->permissions);

                return [
                    'id' => $role->id,
                    'project_id' => $role->project_id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'description' => $role->description,
                    'is_default' => $role->is_default,
                    'is_active' => $role->is_active,
                    'permissions_count' => $role->permissions->count(),
                    'permissions' => $permissionsTree,
                    'created_at' => $role->created_at?->toISOString(),
                ];
            });

            return Json::items($data->toArray());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $projectId = $request->route('project_id');

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'sometimes|string|max:255',
                'description' => 'sometimes|string|nullable',
                'is_active' => 'sometimes|boolean',
                'permission_ids' => 'sometimes|array',
                'permission_ids.*' => 'uuid|exists:project_permissions,id',
            ]);

            $permissionIds = $validated['permission_ids'] ?? [];
            unset($validated['permission_ids']);

            $role = $this->service->createRole($projectId, $validated, $permissionIds);

            return Json::item($role, 201);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

    public function show(Request $request): JsonResponse
    {
        try {
            $id = $request->route('id');
            $role = $this->service->getProjectRoles($request->route('project_id'))
                ->firstWhere('id', $id);

            if (!$role) {
                return Json::error('Role not found', 404);
            }

            $presenter = new ProjectPermissionLookupPresenter();
            $permissionsTree = $presenter->present($role->permissions);

            $data = [
                'id' => $role->id,
                'project_id' => $role->project_id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'is_default' => $role->is_default,
                'is_active' => $role->is_active,
                'permissions_count' => $role->permissions->count(),
                'permissions' => $permissionsTree,
                'created_at' => $role->created_at?->toISOString(),
                'updated_at' => $role->updated_at?->toISOString(),
            ];

            return Json::item($data);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $id = $request->route('id');

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'slug' => 'sometimes|string|max:255',
                'description' => 'sometimes|string|nullable',
                'is_active' => 'sometimes|boolean',
                'permission_ids' => 'sometimes|array',
                'permission_ids.*' => 'uuid|exists:project_permissions,id',
            ]);

            $permissionIds = isset($validated['permission_ids']) ? $validated['permission_ids'] : null;
            unset($validated['permission_ids']);

            $role = $this->service->updateRole($id, $validated, $permissionIds);

            return Json::item($role);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

    public function delete(Request $request): JsonResponse
    {
        try {
            $id = $request->route('id');
            $this->service->deleteRole($id);

            return Json::deleted();
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

    public function assignPermissions(Request $request): JsonResponse
    {
        try {
            $id = $request->route('id');

            $validated = $request->validate([
                'permission_ids' => 'required|array',
                'permission_ids.*' => 'uuid|exists:project_permissions,id',
            ]);

            $role = $this->service->assignPermissions($id, $validated['permission_ids']);

            return Json::item($role);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

    public function syncPermissions(Request $request): JsonResponse
    {
        try {
            $id = $request->route('id');

            $validated = $request->validate([
                'permission_ids' => 'required|array',
                'permission_ids.*' => 'uuid|exists:project_permissions,id',
            ]);

            $role = $this->service->syncPermissions($id, $validated['permission_ids']);

            return Json::item($role);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }
}
