<?php

declare(strict_types=1);

namespace Modules\Shared\Currency\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\Currency\Models\Currency;

/**
 * @property Currency $model
 * @method Currency findOneOrFail($id)
 * @method Currency findOneByOrFail(array $data)
 */
class CurrencyRepository extends BaseRepository
{
    public function __construct(Currency $model)
    {
        parent::__construct($model);
    }

    public function getCurrencyList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getCurrency(UuidInterface $id): Currency
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createCurrency(array $data): Currency
    {
        return $this->create($data);
    }

    public function updateCurrency(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteCurrency(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
