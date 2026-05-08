<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Project\ProjectManagement\Models\ProjectPermission;
use Illuminate\Database\Eloquent\Collection;

class ProjectPermissionRepository extends BaseRepository
{
    public function __construct(ProjectPermission $model)
    {
        parent::__construct($model);
    }

    public function getAllActive(): Collection
    {
        return $this->model
            ->where('is_active', true)
            ->orderBy('submodule')
            ->orderBy('action')
            ->get();
    }

    public function getBySubmodule(string $submodule): Collection
    {
        return $this->model
            ->where('submodule', $submodule)
            ->where('is_active', true)
            ->get();
    }

    public function getByIds(array $ids): Collection
    {
        return $this->model->whereIn('id', $ids)->get();
    }

    public function updatePermission(string $id, array $data): ProjectPermission
    {
        $permission = $this->findOneOrFail($id);
        $permission->update($data);
        return $permission->fresh();
    }
}
