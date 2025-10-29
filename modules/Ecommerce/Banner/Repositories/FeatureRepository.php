<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\Banner\Models\Feature;
use App\Traits\HasExport;

/**
 * @property Feature $model
 * @method Feature findOneOrFail($id)
 * @method Feature findOneByOrFail(array $data)
 */
class FeatureRepository extends BaseRepository
{
    use HasExport;

    public function __construct(Feature $model)
    {
        parent::__construct($model);
    }

    public function getFeatureList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getFeature(UuidInterface $id): Feature
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createFeature(array $data): Feature
    {
        return $this->create($data);
    }

    public function updateFeature(UuidInterface $id, array $data): Feature
    {
        $feature = $this->getFeature($id);
        $feature->update($data);
        return $feature->fresh();
    }

    public function deleteFeature(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function toggleStatus(UuidInterface $id): Feature
    {
        $feature = $this->getFeature($id);
        $newStatus = !$feature->is_active;
        $feature->update(['is_active' => $newStatus]);
        return $feature->fresh();
    }

    public function getByCompany(UuidInterface $companyId): Collection
    {
        return $this->model->byCompany($companyId->toString())->get();
    }

    public function getActiveFeatures(): Collection
    {
        return $this->model->active()->get();
    }
}
