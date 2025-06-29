<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Package\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\SubscriptionSystem\Package\Models\Package;

/**
 * @property Package $model
 * @method Package findOneOrFail($id)
 * @method Package findOneByOrFail(array $data)
 */
class PackageRepository extends BaseRepository
{
    public function __construct(Package $model)
    {
        parent::__construct($model);
    }

    public function getPackageList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getPackage(UuidInterface $id): Package
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createPackage(array $data): Package
    {
        return $this->create($data);
    }

    public function updatePackage(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deletePackage(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
