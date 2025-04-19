<?php

declare(strict_types=1);

namespace Modules\Shared\Privilege\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\Privilege\Models\Privilege;

/**
 * @property Privilege $model
 * @method Privilege findOneOrFail($id)
 * @method Privilege findOneByOrFail(array $data)
 */
class PrivilegeRepository extends BaseRepository
{
    public function __construct(Privilege $model)
    {
        parent::__construct($model);
    }

    public function getPrivilegeList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getPrivilege(UuidInterface $id): Privilege
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createPrivilege(array $data): Privilege
    {
        return $this->create($data);
    }

    public function updatePrivilege(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deletePrivilege(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
