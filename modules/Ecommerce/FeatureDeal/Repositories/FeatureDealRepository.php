<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FeatureDeal\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\FeatureDeal\Models\FeatureDeal;
use App\Traits\HasExport;

/**
 * @property FeatureDeal $model
 * @method FeatureDeal findOneOrFail($id)
 * @method FeatureDeal findOneByOrFail(array $data)
 */
class FeatureDealRepository extends BaseRepository
{
    use HasExport;

    public function __construct(FeatureDeal $model)
    {
        parent::__construct($model);
    }

    public function getFeatureDealList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getFeatureDeal(UuidInterface $id): FeatureDeal
    {
        return $this->model->with(['products'])
            ->where('id', $id->toString())
            ->firstOrFail();
    }

    public function getFeatureDealWithRelations(UuidInterface $id): FeatureDeal
    {
        return $this->model->with(['company', 'products'])
            ->where('id', $id->toString())
            ->firstOrFail();
    }

    public function createFeatureDeal(array $data, array $productIds = []): FeatureDeal
    {
        $featureDeal = $this->create($data);
        if (!empty($productIds)) {
            $featureDeal->products()->sync($productIds);
        }
        return $featureDeal->load('products');
    }

    public function updateFeatureDeal(UuidInterface $id, array $data, ?array $productIds = null): FeatureDeal
    {
        $featureDeal = $this->getFeatureDeal($id);
        $featureDeal->update($data);

        if ($productIds !== null) {
            $featureDeal->products()->sync($productIds);
        }

        return $featureDeal->fresh(['products']);
    }

    public function deleteFeatureDeal(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Get feature deals for export with filters
     */
    public function getForExport(array $filters = [])
    {
        $query = $this->model->newQuery();
        
        // Load relationships for export
        $query->with(['company']);
        
        // Apply filters using the filterable trait
        $query->filter($filters);
        
        // Order by created_at desc for consistent export
        $query->orderBy('created_at', 'desc');
        
        return $query->get();
    }

    public function paginatedWithRelations(int $page = 1, int $perPage = 10, array $relations = []): array
    {
        $query = $this->model->newQuery();

        if (!empty($relations)) {
            $query->with($relations);
        }

        $query->filter(request()->all());


        $total = $query->count();
        $items = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->orderBy('created_at', 'desc')
            ->get();

        $lastPage = (int) ceil($total / $perPage);
        $nextPage = $page < $lastPage ? $page + 1 : $lastPage;
        $resultCount = $items->count();

        return [
            'data' => $items,
            'pagination' => [
                'page' => $page,
                'next_page' => $nextPage,
                'last_page' => $lastPage,
                'result_count' => $resultCount,
            ]
        ];
    }
}
