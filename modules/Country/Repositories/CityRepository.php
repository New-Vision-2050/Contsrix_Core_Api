<?php

declare(strict_types=1);

namespace Modules\Country\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Country\Models\City;
use Ramsey\Uuid\UuidInterface;
use Modules\Country\Models\Country;

/**
 * @property City $model
 * @method City findOneOrFail($id)
 * @method City findOneByOrFail(array $data)
 */
class CityRepository extends BaseRepository
{
    public function __construct(City $model)
    {
        parent::__construct($model);
    }

    public function getCountryList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList(['status' => 1], $page, $perPage);
    }

    public function getCity(UuidInterface $id): City
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createCountry(array $data): City
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

    public function findBySimplifiedWay($simplifiedName):?City
    {
        $city = $this->model->whereRaw('LOWER(REGEXP_REPLACE(name, \'[^a-zA-Z]\', \'\')) = ?', [$simplifiedName])->first();
        return $city;

    }
}
