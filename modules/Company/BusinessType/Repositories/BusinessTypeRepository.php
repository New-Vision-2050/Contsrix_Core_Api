<?php

declare(strict_types=1);

namespace Modules\Company\BusinessType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Company\BusinessType\Models\BusinessType;

/**
 * @property BusinessType $model
 * @method BusinessType findOneOrFail($id)
 * @method BusinessType findOneByOrFail(array $data)
 */
class BusinessTypeRepository extends BaseRepository
{
    public function __construct(BusinessType $model)
    {
        parent::__construct($model);
    }

    public function getBusinessTypeList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getBusinessType(UuidInterface $id): BusinessType
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createBusinessType(array $data): BusinessType
    {
        return $this->create($data);
    }

    public function updateBusinessType(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteBusinessType(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
