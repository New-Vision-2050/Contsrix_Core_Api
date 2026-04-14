<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Modules\Project\ProjectManagement\Repositories\ProjectRoleRepository;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ProjectRoleService
{
    public function __construct(
        private ProjectRoleRepository $repository
    ) {
    }

    public function getProjectRoles(string $projectId): Collection
    {
        $project = ProjectManagement::findOrFail($projectId);
        return $this->repository->getByProject($projectId);
    }

    public function createRole(string $projectId, array $data, array $permissionIds = []): array
    {
        $project = ProjectManagement::findOrFail($projectId);
        
        $roleData = [
            'project_id' => $projectId,
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ];
        
        $role = $this->repository->createRole($roleData, $permissionIds);
        
        return $this->formatRoleData($role);
    }

    public function updateRole(string $id, array $data, ?array $permissionIds = null): array
    {
        $updateData = array_filter([
            'name' => $data['name'] ?? null,
            'slug' => isset($data['slug']) ? $data['slug'] : (isset($data['name']) ? Str::slug($data['name']) : null),
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? null,
        ], fn($value) => $value !== null);
        
        $role = $this->repository->updateRole($id, $updateData, $permissionIds);
        
        return $this->formatRoleData($role);
    }

    public function assignPermissions(string $roleId, array $permissionIds): array
    {
        $role = $this->repository->assignPermissions($roleId, $permissionIds);
        return $this->formatRoleData($role);
    }

    public function syncPermissions(string $roleId, array $permissionIds): array
    {
        $role = $this->repository->syncPermissions($roleId, $permissionIds);
        return $this->formatRoleData($role);
    }

    public function deleteRole(string $id): bool
    {
        $role = $this->repository->findOneOrFail($id);
        
        if ($role->is_default) {
            throw new \Exception('Cannot delete default role');
        }
        
        return $this->repository->delete($id);
    }

    private function formatRoleData($role): array
    {
        return [
            'id' => $role->id,
            'project_id' => $role->project_id,
            'name' => $role->name,
            'slug' => $role->slug,
            'description' => $role->description,
            'is_default' => $role->is_default,
            'is_active' => $role->is_active,
            'permissions' => $role->permissions->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'submodule' => $p->submodule,
                'action' => $p->action,
                'title' => $p->title,
            ]),
            'created_at' => $role->created_at?->toISOString(),
            'updated_at' => $role->updated_at?->toISOString(),
        ];
    }
}
