<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Project\ProjectManagement\Models\ProjectRole;
use Illuminate\Database\Eloquent\Collection;

class ProjectRoleRepository extends BaseRepository
{
    public function __construct(ProjectRole $model)
    {
        parent::__construct($model);
    }

    public function getByProject(string $projectId): Collection
    {
        return $this->model
            ->where('project_id', $projectId)
            ->with('permissions')
            ->get();
    }

    public function getActiveByProject(string $projectId): Collection
    {
        return $this->model
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->with('permissions')
            ->get();
    }

    public function createRole(array $data, array $permissionIds = []): ProjectRole
    {
        $role = $this->create($data);
        
        if (!empty($permissionIds)) {
            $role->permissions()->sync($permissionIds);
        }
        
        return $role->fresh(['permissions']);
    }

    public function updateRole(string $id, array $data, ?array $permissionIds = null): ProjectRole
    {
        $role = $this->findOneOrFail($id);
        $role->update($data);
        
        if ($permissionIds !== null) {
            $role->permissions()->sync($permissionIds);
        }
        
        return $role->fresh(['permissions']);
    }

    public function assignPermissions(string $roleId, array $permissionIds): ProjectRole
    {
        $role = $this->findOneOrFail($roleId);
        $role->permissions()->attach($permissionIds);
        return $role->fresh(['permissions']);
    }

    public function syncPermissions(string $roleId, array $permissionIds): ProjectRole
    {
        $role = $this->findOneOrFail($roleId);
        $role->permissions()->sync($permissionIds);
        return $role->fresh(['permissions']);
    }

    public function removePermissions(string $roleId, array $permissionIds): ProjectRole
    {
        $role = $this->findOneOrFail($roleId);
        $role->permissions()->detach($permissionIds);
        return $role->fresh(['permissions']);
    }

    public function findBySlug(string $projectId, string $slug): ?ProjectRole
    {
        return $this->model
            ->where('project_id', $projectId)
            ->where('slug', $slug)
            ->first();
    }
}
