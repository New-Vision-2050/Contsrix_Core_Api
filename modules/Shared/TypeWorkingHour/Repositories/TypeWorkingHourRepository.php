<?php

declare(strict_types=1);

namespace Modules\Shared\TypeWorkingHour\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\TypeWorkingHour\Models\TypeWorkingHour;

/**
 * @property TypeWorkingHour $model
 * @method TypeWorkingHour findOneOrFail($id)
 * @method TypeWorkingHour findOneByOrFail(array $data)
 */
class TypeWorkingHourRepository extends BaseRepository
{
    public function __construct(TypeWorkingHour $model)
    {
        parent::__construct($model);
    }

    public function getTypeWorkingHourList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getTypeWorkingHour(UuidInterface $id): TypeWorkingHour
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createTypeWorkingHour(array $data): TypeWorkingHour
    {
        return $this->create($data);
    }

    public function updateTypeWorkingHour(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteTypeWorkingHour(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
