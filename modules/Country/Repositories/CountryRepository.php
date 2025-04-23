<?php

declare(strict_types=1);

namespace Modules\Country\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Country\Models\City;
use Modules\Country\Models\State;
use Normalizer;
use Ramsey\Uuid\UuidInterface;
use Modules\Country\Models\Country;
use GuzzleHttp\Client as GuzzleClient;

/**
 * @property Country $model
 * @method Country findOneOrFail($id)
 * @method Country findOneByOrFail(array $data)
 */
class CountryRepository extends BaseRepository
{
    public function __construct(Country $model)
    {
        parent::__construct($model);
    }

    public function getCountryList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList(['status' => 1], $page, $perPage);
    }

    public function getCountry(UuidInterface $id): Country
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createCountry(array $data): Country
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

    public function getCountryWithSatesWithCities( $countryId = null , $stateId = null)
    {
    $data = [];
       if ($countryId== null && $stateId == null) {
           $data = $this->model->get();

       }
       elseif ($countryId!= null) {
           $data = State::query()->where("country_id", $countryId)->get();

       }elseif ($stateId!= null) {
           $data = City::query()->where("state_id", $stateId)->get();
       }

        return $data;
    }

    public function findBySimplifiedWay($simplifiedName):?Country
    {
        $country = $this->model->whereRaw('LOWER(name) = ?', [$simplifiedName])->first();
        return $country;

    }
}
