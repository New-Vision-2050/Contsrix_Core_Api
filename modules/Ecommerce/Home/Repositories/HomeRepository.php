<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Home\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\Home\Models\Home;
use App\Traits\HasExport;

/**
 * @property Home $model
 * @method Home findOneOrFail($id)
 * @method Home findOneByOrFail(array $data)
 */
class HomeRepository extends BaseRepository
{
    use HasExport;

    public function __construct(Home $model)
    {
        parent::__construct($model);
    }

    public function getHomeList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getHome(UuidInterface $id): Home
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createHome(array $data): Home
    {
        return $this->create($data);
    }

    public function updateHome(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteHome(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
