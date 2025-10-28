<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Order\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\Order\Models\Order;
use App\Traits\HasExport;

/**
 * @property Order $model
 * @method Order findOneOrFail($id)
 * @method Order findOneByOrFail(array $data)
 */
class OrderRepository extends BaseRepository
{
    use HasExport;

    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    public function getOrderList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getOrder(UuidInterface $id): Order
    {
        return $this->model->with(['company', 'customer', 'warehouse'])
            ->where('id', $id->toString())
            ->firstOrFail();
    }

    public function paginated(array $conditions = [], int $page = 1, int $perPage = 15, string $orderBy = 'created_at', string $sortBy = 'desc'): array
    {
        $query = $this->model->query()
            ->with(['company', 'customer', 'warehouse'])
            ->orderBy($orderBy, $sortBy);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }

    public function createOrder(array $data): Order
    {
        return $this->create($data);
    }

    public function updateOrder(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteOrder(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
