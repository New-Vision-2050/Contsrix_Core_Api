<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\RoleAndPermission\Models\Permission;
use Ramsey\Uuid\UuidInterface;


/**
 * @property Permission $model
 * @method Permission findOneOrFail($id)
 * @method Permission findOneByOrFail(array $data)
 */
class PermissionRepository extends BaseRepository
{
    public function __construct(Permission $model)
    {
        parent::__construct($model);
    }

    public function getPermissionList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getPermissionsWithoutPagination()
    {
        return $this->model->get();
    }

    public function getPermission(UuidInterface $id): Permission
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createPermission(array $data): Permission
    {
        return $this->create($data);
    }

    public function updatePermission(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deletePermission(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Find permission by name.
     */
    public function findByName(string $name): ?Permission
    {
        return $this->model->where('name', $name)
            ->first();
    }

    /**
     * Get permissions filtered by subEntity names where subEntity matches
     * the second part of permission name pattern "programName.subEntity.action"
     */
    public function getPermissionsBySubEntities(array $subEntities)
    {
        if (empty($subEntities)) {
            return collect();
        }

        return $this->model->where(function ($query) use ($subEntities) {
            foreach ($subEntities as $subEntity) {
                // Escape special regex characters in subEntity to treat them as literals
                $escapedSubEntity = preg_quote($subEntity, '/');
                // Use REGEXP to match exactly the second segment: "anything.subEntity.anything"
                $query->orWhere('name', 'REGEXP', "^[^.]+\\.{$escapedSubEntity}\\.[^.]+");
            }
        })->get();
    }
}
