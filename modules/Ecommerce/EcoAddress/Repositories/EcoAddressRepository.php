<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoAddress\Models\EcoAddress;
use App\Traits\HasExport;

/**
 * @property EcoAddress $model
 * @method EcoAddress findOneOrFail($id)
 * @method EcoAddress findOneByOrFail(array $data)
 */
class EcoAddressRepository extends BaseRepository
{
    use HasExport;

    public function __construct(EcoAddress $model)
    {
        parent::__construct($model);
    }

    public function getEcoAddressList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getEcoAddress(UuidInterface $id): EcoAddress
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createEcoAddress(array $data): EcoAddress
    {
        return $this->create($data);
    }

    public function updateEcoAddress(UuidInterface $id, array $data): bool
    {
        $address = $this->getEcoAddress($id);

        if ($data['is_default'] === 1) {
            $address->client->addresses()
                    ->where('is_default', 1)
                    ->update(['is_default' => 0]);
        }

        return $this->update($id, $data);
    }

    public function deleteEcoAddress(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
