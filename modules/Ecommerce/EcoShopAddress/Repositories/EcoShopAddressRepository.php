<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShopAddress\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoShopAddress\Models\EcoShopAddress;
use App\Traits\HasExport;

/**
 * @property EcoShopAddress $model
 * @method EcoShopAddress findOneOrFail($id)
 * @method EcoShopAddress findOneByOrFail(array $data)
 */
class EcoShopAddressRepository extends BaseRepository
{
    use HasExport;

    public function __construct(EcoShopAddress $model)
    {
        parent::__construct($model);
    }

    public function getEcoShopAddressList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getEcoShopAddress(UuidInterface $companyId): EcoShopAddress
    {
        return $this->findOneByOrFail([
            'company_id' => $companyId->toString(),
        ]);
    }

    public function findByCompanyId(UuidInterface $companyId): ?EcoShopAddress
    {
        return $this->findOneBy([
            'company_id' => $companyId->toString(),
        ]);
    }

    public function createEcoShopAddress(array $data): EcoShopAddress
    {
        return $this->create($data);
    }

    public function updateEcoShopAddress(UuidInterface $companyId, array $data)
    {
        return $this->updateWhere(['company_id' => $companyId->toString()], $data);
    }

    public function deleteEcoShopAddress(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
