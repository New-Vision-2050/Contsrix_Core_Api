<?php

declare(strict_types=1);

namespace Modules\Ecommerce\PaymentMethod\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\PaymentMethod\Models\PaymentMethod;
use App\Traits\HasExport;

/**
 * @property PaymentMethod $model
 * @method PaymentMethod findOneOrFail($id)
 * @method PaymentMethod findOneByOrFail(array $data)
 */
class PaymentMethodRepository extends BaseRepository
{
    use HasExport;

    public function __construct(PaymentMethod $model)
    {
        parent::__construct($model);
    }

    public function getPaymentMethodList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getPaymentMethod(UuidInterface $id): PaymentMethod
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createPaymentMethod(array $data): PaymentMethod
    {
        return $this->create($data);
    }

    public function updatePaymentMethod(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deletePaymentMethod(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
