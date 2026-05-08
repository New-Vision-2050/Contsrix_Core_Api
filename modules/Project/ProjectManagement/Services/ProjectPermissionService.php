<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Modules\Project\ProjectManagement\Repositories\ProjectPermissionRepository;
use Illuminate\Database\Eloquent\Collection;

class ProjectPermissionService
{
    public function __construct(
        private ProjectPermissionRepository $repository
    ) {
    }

    public function getAllPermissions(): Collection
    {
        return $this->repository->getAllActive();
    }

    public function getPermissionsBySubmodule(string $submodule): Collection
    {
        return $this->repository->getBySubmodule($submodule);
    }

    public function updatePermission(string $id, array $data): array
    {
        $permission = $this->repository->updatePermission($id, $data);
        
        return [
            'id' => $permission->id,
            'name' => $permission->name,
            'submodule' => $permission->submodule,
            'action' => $permission->action,
            'title' => $permission->title,
            'description' => $permission->description,
        ];
    }
}
