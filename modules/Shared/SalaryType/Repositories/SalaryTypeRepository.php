<?php

declare(strict_types=1);

namespace Modules\Shared\SalaryType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\SalaryType\Models\SalaryType;

/**
 * @property SalaryType $model
 * @method SalaryType findOneOrFail($id)
 * @method SalaryType findOneByOrFail(array $data)
 */
class SalaryTypeRepository extends BaseRepository
{
    public function __construct(SalaryType $model)
    {
        parent::__construct($model);
    }

    public function getSalaryTypeList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getSalaryType(UuidInterface $id): SalaryType
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createSalaryType(array $data): SalaryType
    {
        return $this->create($data);
    }

    public function updateSalaryType(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteSalaryType(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
