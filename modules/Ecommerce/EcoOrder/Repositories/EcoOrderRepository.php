<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrder\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoOrder\Models\EcoOrder;
use Modules\Ecommerce\EcoOrderDetail\Models\EcoOrderDetail;
use App\Traits\HasExport;

/**
 * @property EcoOrder $model
 * @method EcoOrder findOneOrFail($id)
 * @method EcoOrder findOneByOrFail(array $data)
 */
class EcoOrderRepository extends BaseRepository
{
    use HasExport;

    public function __construct(EcoOrder $model)
    {
        parent::__construct($model);
    }

    public function getEcoOrderList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getEcoOrder(UuidInterface $id): EcoOrder
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createEcoOrder(array $data): EcoOrder
    {
        return DB::transaction(function () use ($data) {
            // Extract details from data
            $details = $data['details'] ?? [];
            unset($data['details']);
            
            // Create the order
            $order = $this->create($data);
            
            // Create order details if provided
            if (!empty($details)) {
                foreach ($details as $detail) {
                    $detail['eco_order_id'] = $order->id;
                    EcoOrderDetail::create($detail);
                }
            }
            
            return $order;
        });
    }

    public function updateEcoOrder(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteEcoOrder(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
