<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;

/**
 * @property ManagementHierarchy $model
 * @method ManagementHierarchy findOneOrFail($id)
 * @method ManagementHierarchy findOneByOrFail(array $data)
 */
class ManagementHierarchyRepository extends BaseRepository
{
    public function __construct(ManagementHierarchy $model)
    {
        parent::__construct($model);
    }

    public function getManagementHierarchyList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getManagementHierarchy(UuidInterface $id): ManagementHierarchy
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createManagementHierarchy(array $data): ManagementHierarchy
    {
        return $this->create($data);
    }

    public function updateManagementHierarchy(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteManagementHierarchy(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
