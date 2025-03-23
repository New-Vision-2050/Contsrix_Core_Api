<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Tenant\Models\Tenant;

/**
 * @property Tenant $model
 * @method Tenant findOneOrFail($id)
 * @method Tenant findOneByOrFail(array $data)
 */
class TenantRepository extends BaseRepository
{
    public function __construct(Tenant $model)
    {
        parent::__construct($model);
    }

    public function getTenantList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getTenant(UuidInterface $id): Tenant
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createTenant(array $data): Tenant
    {
        return $this->create($data);
    }

    public function updateTenant(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteTenant(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
