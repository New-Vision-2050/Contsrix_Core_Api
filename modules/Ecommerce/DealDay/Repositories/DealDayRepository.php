<?php

declare(strict_types=1);

namespace Modules\Ecommerce\DealDay\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\DealDay\Models\DealDay;
use App\Traits\HasExport;

/**
 * @property DealDay $model
 * @method DealDay findOneOrFail($id)
 * @method DealDay findOneByOrFail(array $data)
 */
class DealDayRepository extends BaseRepository
{
    use HasExport;

    public function __construct(DealDay $model)
    {
        parent::__construct($model);
    }

    public function getDealDayList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getDealDay(UuidInterface $id): DealDay
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function getDealDayWithRelations(UuidInterface $id): DealDay
    {
        return $this->model->with(['company', 'product'])
            ->where('id', $id->toString())
            ->firstOrFail();
    }

    public function createDealDay(array $data): DealDay
    {
        return $this->create($data);
    }

    public function updateDealDay(UuidInterface $id, array $data): DealDay
    {
        $dealDay = $this->getDealDay($id);
        $dealDay->update($data);
        return $dealDay->fresh();
    }

    public function deleteDealDay(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function paginatedWithRelations(int $page = 1, int $perPage = 10, array $relations = [], array $filters = []): array
    {
        $query = $this->model->newQuery();

        if (!empty($relations)) {
            $query->with($relations);
        }

        // Apply filters if provided
        if (!empty($filters)) {
            $query = $this->applyFilters($query, $filters);
        }

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
                'total' => $total,
            ]
        ];
    }

    /**
     * Apply filters to query using DealDayFilter
     */
    private function applyFilters($query, array $filters)
    {
        foreach ($filters as $key => $value) {
            if ($value !== null && $value !== '') {
                switch ($key) {
                    case 'search':
                        $query->where(function ($q) use ($value) {
                            $q->whereHas('translations', function ($translationQuery) use ($value) {
                                  $translationQuery->where('content', 'like', '%' . $value . '%');
                              })
                              ->orWhere('discount_type', 'like', '%' . $value . '%')
                              ->orWhereHas('product', function ($productQuery) use ($value) {
                                  $productQuery->whereHas('translations', function ($translationQuery) use ($value) {
                                      $translationQuery->where('content', 'like', '%' . $value . '%');
                                  })
                                               ->orWhere('sku', 'like', '%' . $value . '%');
                              })
                              ->orWhereHas('company', function ($companyQuery) use ($value) {
                                  $companyQuery->whereHas('translations', function ($translationQuery) use ($value) {
                                      $translationQuery->where('content', 'like', '%' . $value . '%');
                                  });
                              });
                        });
                        break;
                    case 'name':
                        $query->whereHas('translations', function ($translationQuery) use ($value) {
                            $translationQuery->where('content', 'like', '%' . $value . '%');
                        });
                        break;
                    case 'company_id':
                        $query->where('company_id', $value);
                        break;
                    case 'product_id':
                        $query->where('product_id', $value);
                        break;
                    case 'discount_type':
                        $query->where('discount_type', $value);
                        break;
                    case 'min_discount_value':
                        $query->where('discount_value', '>=', $value);
                        break;
                    case 'max_discount_value':
                        $query->where('discount_value', '<=', $value);
                        break;
                    case 'is_active':
                        $query->where('is_active', (bool) $value);
                        break;
                    case 'active_only':
                        if ($value) {
                            $query->where('is_active', true);
                        }
                        break;
                    case 'inactive_only':
                        if ($value) {
                            $query->where('is_active', false);
                        }
                        break;
                    case 'created_from':
                        $query->whereDate('created_at', '>=', $value);
                        break;
                    case 'created_to':
                        $query->whereDate('created_at', '<=', $value);
                        break;
                    case 'updated_from':
                        $query->whereDate('updated_at', '>=', $value);
                        break;
                    case 'updated_to':
                        $query->whereDate('updated_at', '<=', $value);
                        break;
                    case 'order_by':
                        $direction = $filters['order_direction'] ?? 'asc';
                        switch ($value) {
                            case 'name':
                                $query->orderBy('name', $direction);
                                break;
                            case 'discount_value':
                                $query->orderBy('discount_value', $direction);
                                break;
                            case 'created_at':
                                $query->orderBy('created_at', $direction);
                                break;
                            default:
                                $query->orderBy('created_at', 'desc');
                        }
                        break;
                }
            }
        }

        return $query;
    }

    /**
     * Search deal days with filters
     */
    public function searchDealDays(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        return $this->paginatedWithRelations($page, $perPage, ['company', 'product'], $filters);
    }

    /**
     * Get deal days for export with filters
     */
    public function getForExport(array $filters = [])
    {
        $query = $this->model->newQuery();
        
        // Load relationships for export
        $query->with(['company', 'product']);
        
        // Apply filters if provided
        if (!empty($filters)) {
            $query = $this->applyFilters($query, $filters);
        }
        
        // Order by created_at desc for consistent export
        $query->orderBy('created_at', 'desc');
        
        return $query->get();
    }
}