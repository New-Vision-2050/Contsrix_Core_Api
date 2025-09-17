<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCurrency\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoCurrency\Models\EcoCurrency;
use App\Traits\HasExport;

/**
 * @property EcoCurrency $model
 * @method EcoCurrency findOneOrFail($id)
 * @method EcoCurrency findOneByOrFail(array $data)
 */
class EcoCurrencyRepository extends BaseRepository
{
    use HasExport;

    public function __construct(EcoCurrency $model)
    {
        parent::__construct($model);
    }

    public function getEcoCurrencyList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getEcoCurrency(UuidInterface $id): EcoCurrency
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createEcoCurrency(array $data): EcoCurrency
    {
        return $this->create($data);
    }

    public function updateEcoCurrency(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function findByCompanyAndCurrency(UuidInterface $companyId, string $currencyId): ?EcoCurrency
    {
        return $this->model->where('company_id', $companyId->toString())
                          ->where('currency_id', $currencyId)
                          ->first();
    }

    public function resetDefaultCurrencies(UuidInterface $companyId): bool
    {
        $this->updateWhere([
            'company_id' => $companyId->toString(),
        ], ['is_default' => 0]);
        
        return true;
    }

    public function deleteCompanyCurrencies(UuidInterface $companyId): bool
    {
        return $this->model->forCompany($companyId->toString())->delete();
    }

    public function deleteEcoCurrency(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
