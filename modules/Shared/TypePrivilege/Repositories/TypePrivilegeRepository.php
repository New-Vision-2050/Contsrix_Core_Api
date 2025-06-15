<?php

declare(strict_types=1);

namespace Modules\Shared\TypePrivilege\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\TypePrivilege\Models\TypePrivilege;

/**
 * @property TypePrivilege $model
 * @method TypePrivilege findOneOrFail($id)
 * @method TypePrivilege findOneByOrFail(array $data)
 */
class TypePrivilegeRepository extends BaseRepository
{
    public function __construct(TypePrivilege $model)
    {
        parent::__construct($model);
    }

    public function getTypePrivilegeList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getTypePrivilege(UuidInterface $id): TypePrivilege
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createTypePrivilege(array $data): TypePrivilege
    {
        return $this->create($data);
    }

    public function updateTypePrivilege(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteTypePrivilege(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
