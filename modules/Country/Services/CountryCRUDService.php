<?php

declare(strict_types=1);

namespace Modules\Country\Services;

use Illuminate\Support\Collection;
use Modules\Country\DTO\CreateCountryDTO;
use Modules\Country\Models\Country;
use Modules\Country\Repositories\CityRepository;
use Modules\Country\Repositories\CountryRepository;
use Ramsey\Uuid\UuidInterface;

class CountryCRUDService
{
    public function __construct(
        private CountryRepository $repository,
        private CityRepository $cityRepository,
    ) {
    }

    public function create(CreateCountryDTO $createCountryDTO): Country
    {
         return $this->repository->createCountry($createCountryDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            ['status' => '1'],
            page: $page,
            perPage: $perPage,
        );
    }
        public function listCity(int $page = 1, int $perPage = 10): array
    {
        return $this->cityRepository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }
    public function getList(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Country
    {
        return $this->repository->getCountry(
            id: $id,
        );
    }

    public function getCountryWithStateWithCity()
    {
        return $this->repository->getCountryWithSatesWithCities(request()->country_id,request()->state_id);

    }

    public function getStatesByCountryBranch()

    {
       return $this->repository->getStateWithBranchAuthUser();
    }
}
