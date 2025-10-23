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

    public function paginatedWithRelations(int $page = 1, int $perPage = 10, array $relations = []): array
    {
        $query = $this->model->newQuery();

        if (!empty($relations)) {
            $query->with($relations);
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
            ]
        ];
    }
}