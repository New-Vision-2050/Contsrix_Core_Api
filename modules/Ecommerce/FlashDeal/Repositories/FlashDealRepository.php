<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FlashDeal\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\FlashDeal\Models\FlashDeal;
use App\Traits\HasExport;
use Carbon\Carbon;

/**
 * @property FlashDeal $model
 * @method FlashDeal findOneOrFail($id)
 * @method FlashDeal findOneByOrFail(array $data)
 */
class FlashDealRepository extends BaseRepository
{
    use HasExport;

    public function __construct(FlashDeal $model)
    {
        parent::__construct($model);
    }

    public function getFlashDealList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getFlashDeal(UuidInterface $id): FlashDeal
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createFlashDeal(array $data): FlashDeal
    {
        return $this->create($data);
    }

    public function updateFlashDeal(UuidInterface $id, array $data): FlashDeal
    {
        $this->update($id, $data);
        return $this->getFlashDeal($id);
    }

    public function deleteFlashDeal(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Get overlapping flash deals for a company within date range
     */
    public function getOverlappingDeals(string $companyId, string $startDate, string $endDate): Collection
    {
        return $this->model->where('company_id', $companyId)
            ->where('is_active', true)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                    });
            })
            ->get();
    }

    /**
     * Get currently active flash deals
     */
    public function getCurrentlyActiveDeals(): Collection
    {
        $now = Carbon::now();
        return $this->model->where('is_active', true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->get();
    }

    /**
     * Get upcoming flash deals
     */
    public function getUpcomingDeals(): Collection
    {
        $now = Carbon::now();
        return $this->model->where('is_active', true)
            ->where('start_date', '>', $now)
            ->get();
    }

    /**
     * Get expired flash deals
     */
    public function getExpiredDeals(): Collection
    {
        $now = Carbon::now();
        return $this->model->where('end_date', '<', $now)
            ->get();
    }

    /**
     * Automatically deactivate expired deals
     */
    public function deactivateExpiredDeals(): int
    {
        $now = Carbon::now();
        return $this->model->where('is_active', true)
            ->where('end_date', '<', $now)
            ->update(['is_active' => false]);
    }

    /**
     * Get flash deals by company
     */
    public function getByCompany(string $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Search flash deals with filters and pagination
     */
    public function searchFlashDeals(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        return $this->paginatedWithRelations($page, $perPage, ['company'], $filters);
    }

    /**
     * Get flash deals for export with filters
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

    /**
     * Paginated list with relations and filters
     */
    public function paginatedWithRelations(int $page = 1, int $perPage = 10, array $relations = [], array $filters = []): array
    {
        $query = $this->model->newQuery();

        if (!empty($relations)) {
            $query->with($relations);
        }

        // Apply filters using the filterable trait
        $query->filter($filters);

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
