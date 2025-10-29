<?php

declare(strict_types=1);

namespace Modules\Shared\PaymentMethodData\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\PaymentMethodData\Models\PaymentMethodData;
use App\Traits\HasExport;

/**
 * @property PaymentMethodData $model
 * @method PaymentMethodData findOneOrFail($id)
 * @method PaymentMethodData findOneByOrFail(array $data)
 */
class PaymentMethodDataRepository extends BaseRepository
{
    use HasExport;

    public function __construct(PaymentMethodData $model)
    {
        parent::__construct($model);
    }

    public function getPaymentMethodDataList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getPaymentMethodData(UuidInterface $id): PaymentMethodData
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createPaymentMethodData(array $data): PaymentMethodData
    {
        return $this->create($data);
    }

    public function updatePaymentMethodData(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deletePaymentMethodData(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
