<?php

declare(strict_types=1);

namespace Modules\Shared\Payment\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\Payment\Models\Payment;
use App\Traits\HasExport;

/**
 * @property Payment $model
 * @method Payment findOneOrFail($id)
 * @method Payment findOneByOrFail(array $data)
 */
class PaymentRepository extends BaseRepository
{
    use HasExport;

    public function __construct(Payment $model)
    {
        parent::__construct($model);
    }

    public function getPaymentList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getPayment(UuidInterface $id): Payment
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createPayment(array $data): Payment
    {
        return $this->create($data);
    }

    public function updatePayment(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deletePayment(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
