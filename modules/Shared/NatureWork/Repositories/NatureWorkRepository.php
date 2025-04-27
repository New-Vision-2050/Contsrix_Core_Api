<?php

declare(strict_types=1);

namespace Modules\Shared\NatureWork\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\NatureWork\Models\NatureWork;

/**
 * @property NatureWork $model
 * @method NatureWork findOneOrFail($id)
 * @method NatureWork findOneByOrFail(array $data)
 */
class NatureWorkRepository extends BaseRepository
{
    public function __construct(NatureWork $model)
    {
        parent::__construct($model);
    }

    public function getNatureWorkList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getNatureWork(UuidInterface $id): NatureWork
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createNatureWork(array $data): NatureWork
    {
        return $this->create($data);
    }

    public function updateNatureWork(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteNatureWork(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
