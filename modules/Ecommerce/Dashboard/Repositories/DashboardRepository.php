<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Dashboard\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\Dashboard\Models\Dashboard;
use App\Traits\HasExport;

/**
 * @property Dashboard $model
 * @method Dashboard findOneOrFail($id)
 * @method Dashboard findOneByOrFail(array $data)
 */
class DashboardRepository extends BaseRepository
{
    use HasExport;

    public function __construct(Dashboard $model)
    {
        parent::__construct($model);
    }

    public function getDashboardList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getDashboard(UuidInterface $id): Dashboard
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createDashboard(array $data): Dashboard
    {
        return $this->create($data);
    }

    public function updateDashboard(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteDashboard(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
