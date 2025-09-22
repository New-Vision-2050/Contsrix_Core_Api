<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoPayment\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoPayment\Models\EcoPayment;
use App\Traits\HasExport;

/**
 * @property EcoPayment $model
 * @method EcoPayment findOneOrFail($id)
 * @method EcoPayment findOneByOrFail(array $data)
 */
class EcoPaymentRepository extends BaseRepository
{
    use HasExport;

    public function __construct(EcoPayment $model)
    {
        parent::__construct($model);
    }

    public function getEcoPaymentList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getEcoPayment(UuidInterface $id): EcoPayment
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createEcoPayment(array $data): EcoPayment
    {
        return $this->create($data);
    }

    public function updateEcoPayment(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteEcoPayment(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function findByCompanyAndPayment(UuidInterface $companyId, string $paymentId): ?EcoPayment
    {
        return $this->model->where('company_id', $companyId->toString())
                          ->where('payment_id', $paymentId)
                          ->first();
    }

    public function resetDefaultPayments(UuidInterface $companyId): void
    {
        $this->model->where('company_id', $companyId->toString())
                   ->where('is_default', true)
                   ->update(['is_default' => false]);
    }

    public function getPaymentsForCompany(UuidInterface $companyId): Collection
    {
        return $this->model->forCompany($companyId->toString())
                          ->with('payment')
                          ->get();
    }
}
