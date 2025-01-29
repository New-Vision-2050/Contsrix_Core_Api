<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\RoleAndPermission\Models\RoleAndPermission;

/**
 * @property RoleAndPermission $model
 * @method RoleAndPermission findOneOrFail($id)
 * @method RoleAndPermission findOneByOrFail(array $data)
 */
class RoleAndPermissionRepository extends BaseRepository
{
    public function __construct(RoleAndPermission $model)
    {
        parent::__construct($model);
    }

    public function getRoleAndPermissionList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getRoleAndPermission(UuidInterface $id): RoleAndPermission
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createRoleAndPermission(array $data): RoleAndPermission
    {
        return $this->create($data);
    }

    public function updateRoleAndPermission(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteRoleAndPermission(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
