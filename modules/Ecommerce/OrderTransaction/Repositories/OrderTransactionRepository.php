<?php

declare(strict_types=1);

namespace Modules\Ecommerce\OrderTransaction\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\OrderTransaction\Models\OrderTransaction;
use App\Traits\HasExport;

/**
 * @property OrderTransaction $model
 * @method OrderTransaction findOneOrFail($id)
 * @method OrderTransaction findOneByOrFail(array $data)
 */
class OrderTransactionRepository extends BaseRepository
{
    use HasExport;

    public function __construct(OrderTransaction $model)
    {
        parent::__construct($model);
    }

    public function getOrderTransactionList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getOrderTransaction(UuidInterface $id): OrderTransaction
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createOrderTransaction(array $data): OrderTransaction
    {
        return $this->create($data);
    }

    public function updateOrderTransaction(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteOrderTransaction(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
