<?php

declare(strict_types=1);

namespace Modules\Shared\RightTerminate\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\RightTerminate\Models\RightTerminate;

/**
 * @property RightTerminate $model
 * @method RightTerminate findOneOrFail($id)
 * @method RightTerminate findOneByOrFail(array $data)
 */
class RightTerminateRepository extends BaseRepository
{
    public function __construct(RightTerminate $model)
    {
        parent::__construct($model);
    }

    public function getRightTerminateList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getRightTerminate(UuidInterface $id): RightTerminate
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createRightTerminate(array $data): RightTerminate
    {
        return $this->create($data);
    }

    public function updateRightTerminate(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteRightTerminate(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
