<?php

declare(strict_types=1);

namespace Modules\Country\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Country\Models\City;
use Modules\Country\Models\State;
use Ramsey\Uuid\UuidInterface;
use Modules\Country\Models\Country;

/**
 * @property State $model
 * @method State findOneOrFail($id)
 * @method State findOneByOrFail(array $data)
 */
class StateRepository extends BaseRepository
{
    public function __construct(State $model)
    {
        parent::__construct($model);
    }

    public function getCountryList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList(['status' => 1], $page, $perPage);
    }

    public function getCity(UuidInterface $id): State
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createCountry(array $data): State
    {
        return $this->create($data);
    }

    public function updateCountry(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteCountry(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function findBySimplifiedWay($simplifiedName):?State
    {
        $state = $this->model->whereRaw('LOWER(name) = ?', [$simplifiedName])->first();
        return $state;

    }
}
