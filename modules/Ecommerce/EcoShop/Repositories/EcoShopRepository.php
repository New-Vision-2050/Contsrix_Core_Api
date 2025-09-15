<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShop\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoShop\Models\EcoShop;
use App\Traits\HasExport;

/**
 * @property EcoShop $model
 * @method EcoShop findOneOrFail($id)
 * @method EcoShop findOneByOrFail(array $data)
 */
class EcoShopRepository extends BaseRepository
{
    use HasExport;

    public function __construct(EcoShop $model)
    {
        parent::__construct($model);
    }

    public function getEcoShopList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getEcoShop(UuidInterface $companyId): EcoShop
    {
        return $this->findOneByOrFail([
           'company_id' => $companyId->toString()
        ]);
    }

    public function createEcoShop(array $data): EcoShop
    {
        return $this->create($data);
    }

    public function updateEcoShop(UuidInterface $companyId, array $data)
    {
        return $this->updateWhere(['company_id' => $companyId->toString()], $data);
    }

    public function deleteEcoShop(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function findByCompanyId(UuidInterface $companyId): ?EcoShop
    {
        return $this->model->where('company_id', $companyId->toString())->first();
    }
}
