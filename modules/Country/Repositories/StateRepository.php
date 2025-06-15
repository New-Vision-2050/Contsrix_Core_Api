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

<<<<<<< HEAD
    public function getStateList(?int $page, ?int $perPage = 10): Collection
=======
    public function getCountryList(?int $page, ?int $perPage = 10): Collection
>>>>>>> 7be6c72c (merge with stage (first version ))
    {
        return $this->paginatedList(['status' => 1], $page, $perPage);
    }

<<<<<<< HEAD
    public function getStatesWithCities($countryId = null)
    {
        return $this->model->with('cities:id,name,state_id')
            ->where('country_id', $countryId)
            ->where('flag', 1)
            ->orderBy('name')
            ->get(['id','name']);
    }

    public function getState($id): State
    {
        return $this->findOneByOrFail([
            'id' => $id,
        ]);
    }

    public function createState(array $data): State
=======
    public function getCity(UuidInterface $id): State
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createCountry(array $data): State
>>>>>>> 7be6c72c (merge with stage (first version ))
    {
        return $this->create($data);
    }

<<<<<<< HEAD
    public function updateState(UuidInterface $id, array $data): bool
=======
    public function updateCountry(UuidInterface $id, array $data): bool
>>>>>>> 7be6c72c (merge with stage (first version ))
    {
        return $this->update($id, $data);
    }

<<<<<<< HEAD
    public function deleteState(UuidInterface $id): bool
=======
    public function deleteCountry(UuidInterface $id): bool
>>>>>>> 7be6c72c (merge with stage (first version ))
    {
        return $this->delete($id);
    }

    public function findBySimplifiedWay($simplifiedName):?State
    {
        $state = $this->model->whereRaw('LOWER(name) = ?', [$simplifiedName])->first();
        return $state;

    }
}
