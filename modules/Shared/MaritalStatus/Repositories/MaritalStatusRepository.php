<?php

declare(strict_types=1);

namespace Modules\Shared\MaritalStatus\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\MaritalStatus\Models\MaritalStatus;

/**
 * @property MaritalStatus $model
 * @method MaritalStatus findOneOrFail($id)
 * @method MaritalStatus findOneByOrFail(array $data)
 */
class MaritalStatusRepository extends BaseRepository
{
    public function __construct(MaritalStatus $model)
    {
        parent::__construct($model);
    }

    public function getMaritalStatusList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getMaritalStatus(UuidInterface $id): MaritalStatus
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createMaritalStatus(array $data): MaritalStatus
    {
        return $this->create($data);
    }

    public function updateMaritalStatus(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteMaritalStatus(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
