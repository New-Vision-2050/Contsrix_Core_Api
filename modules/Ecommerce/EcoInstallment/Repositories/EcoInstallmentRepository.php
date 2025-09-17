<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoInstallment\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoInstallment\Models\EcoInstallment;
use App\Traits\HasExport;

/**
 * @property EcoInstallment $model
 * @method EcoInstallment findOneOrFail($id)
 * @method EcoInstallment findOneByOrFail(array $data)
 */
class EcoInstallmentRepository extends BaseRepository
{
    use HasExport;

    public function __construct(EcoInstallment $model)
    {
        parent::__construct($model);
    }

    public function getEcoInstallmentList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getEcoInstallment(UuidInterface $id): EcoInstallment
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createEcoInstallment(array $data): EcoInstallment
    {
        return $this->create($data);
    }

    public function updateEcoInstallment(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteEcoInstallment(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function findByCompanyAndInstallment(UuidInterface $companyId, string $installmentId): ?EcoInstallment
    {
        return $this->model->where('company_id', $companyId->toString())
                          ->where('installment_id', $installmentId)
                          ->first();
    }

    public function resetDefaultInstallments(UuidInterface $companyId): void
    {
        $this->model->where('company_id', $companyId->toString())
                   ->where('is_default', true)
                   ->update(['is_default' => false]);
    }

    public function getInstallmentsForCompany(UuidInterface $companyId): Collection
    {
        return $this->model->forCompany($companyId->toString())
                          ->with('installment')
                          ->get();
    }
}
