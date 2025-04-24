<?php

declare(strict_types=1);

namespace Modules\Shared\TypeAllowance\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\TypeAllowance\Models\TypeAllowance;

/**
 * @property TypeAllowance $model
 * @method TypeAllowance findOneOrFail($id)
 * @method TypeAllowance findOneByOrFail(array $data)
 */
class TypeAllowanceRepository extends BaseRepository
{
    public function __construct(TypeAllowance $model)
    {
        parent::__construct($model);
    }

    public function getTypeAllowanceList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getTypeAllowance(UuidInterface $id): TypeAllowance
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createTypeAllowance(array $data): TypeAllowance
    {
        return $this->create($data);
    }

    public function updateTypeAllowance(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteTypeAllowance(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
