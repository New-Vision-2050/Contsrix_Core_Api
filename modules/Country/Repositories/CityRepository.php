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

    /**
     * Get paginated list of cities
     */
    public function getCityList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList(['status' => 1], $page, $perPage);
    }

    /**
     * Get city by ID
     */
    public function getCity($id): City
    {
        return $this->findOneByOrFail([
            'id' => $id,
        ]);
    }

    /**
     * Create a new city
     */
    public function createCity(array $data): City
    {
        return $this->create($data);
    }

    /**
     * Update city by ID
     */
    public function updateCity(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    /**
     * Delete city by ID
     */
    public function deleteCity(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Find city by simplified name (case-insensitive)
     */
    public function findBySimplifiedWay($simplifiedName): ?City
    {
        $city = $this->model->whereRaw('LOWER(name) = ?', [strtolower(trim($simplifiedName))])->first();
        return $city;
    }

    /**
     * Find cities by country ID
     */
    public function findByCountryId($countryId): Collection
    {
        return $this->model->where('country_id', $countryId)
            ->where('flag', 1)
            ->orderBy('name')
            ->get();
    }

    /**
     * Find cities by state ID
     */
    public function findByStateId($stateId): Collection
    {
        return $this->model->where('state_id', $stateId)
            ->where('flag', 1)
            ->orderBy('name')
            ->get();
    }

    /**
     * Find cities by country code
     */
    public function findByCountryCode(string $countryCode): Collection
    {
        return $this->model->where('country_code', $countryCode)
            ->where('flag', 1)
            ->orderBy('name')
            ->get();
    }

    /**
     * Find cities by state code and country code
     */
    public function findByStateAndCountryCode(string $stateCode, string $countryCode): Collection
    {
        return $this->model->where('state_code', $stateCode)
            ->where('country_code', $countryCode)
            ->where('flag', 1)
            ->orderBy('name')
            ->get();
    }

    /**
     * Search cities by name with fuzzy matching
     */
    public function searchByName(string $name, int $limit = 10): Collection
    {
        $name = strtolower(trim($name));

        return $this->model->whereRaw('LOWER(name) LIKE ?', ["%{$name}%"])
            ->where('flag', 1)
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Find nearest cities by coordinates
     */
    public function findNearestByCoordinates(float $latitude, float $longitude, int $limit = 10): Collection
    {
        return $this->model->selectRaw('*, 
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
            [$latitude, $longitude, $latitude])
            ->where('flag', 1)
            ->orderBy('distance')
            ->limit($limit)
            ->get();
    }

    /**
     * Get cities with their state and country relationships
     */
    public function getCitiesWithRelations(): Collection
    {
        return $this->model->with(['state', 'country'])
            ->where('flag', 1)
            ->orderBy('name')
            ->get();
    }

    /**
     * Find cities by multiple criteria
     */
    public function findByCriteria(array $criteria): Collection
    {
        $query = $this->model->where('flag', 1);

        if (isset($criteria['name'])) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($criteria['name']) . '%']);
        }

        if (isset($criteria['country_id'])) {
            $query->where('country_id', $criteria['country_id']);
        }

        if (isset($criteria['state_id'])) {
            $query->where('state_id', $criteria['state_id']);
        }

        if (isset($criteria['country_code'])) {
            $query->where('country_code', $criteria['country_code']);
        }

        if (isset($criteria['state_code'])) {
            $query->where('state_code', $criteria['state_code']);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get city by exact name match within a specific country
     */
    public function findByNameAndCountry(string $cityName, $countryId): ?City
    {
        return $this->model->whereRaw('LOWER(name) = ?', [strtolower(trim($cityName))])
            ->where('country_id', $countryId)
            ->where('flag', 1)
            ->first();
    }

    /**
     * Get city by exact name match within a specific state
     */
    public function findByNameAndState(string $cityName, $stateId): ?City
    {
        return $this->model->whereRaw('LOWER(name) = ?', [strtolower(trim($cityName))])
            ->where('state_id', $stateId)
            ->where('flag', 1)
            ->first();
    }

    /**
     * Get all cities for a specific country with state information
     */
    public function getCitiesForCountryWithState($countryId): Collection
    {
        return $this->model->with('state')
            ->where('country_id', $countryId)
            ->where('flag', 1)
            ->orderBy('name')
            ->get();
    }

    /**
     * Find cities using Levenshtein distance for fuzzy matching
     */
    public function findNearestMatchByName(string $cityName, $countryId = null, float $threshold = 0.6): ?City
    {
        $query = $this->model->where('flag', 1);

        if ($countryId) {
            $query->where('country_id', $countryId);
        }

        $cities = $query->get();
        $bestMatch = null;
        $bestSimilarity = 0;

        foreach ($cities as $city) {
            $similarity = 1 - (levenshtein(strtolower($cityName), strtolower($city->name)) / max(strlen($cityName), strlen($city->name)));

            if ($similarity > $threshold && $similarity > $bestSimilarity) {
                $bestSimilarity = $similarity;
                $bestMatch = $city;
            }
        }

        return $bestMatch;
    }

    /**
     * Get cities count by country
     */
    public function getCitiesCountByCountry(): array
    {
        return $this->model->selectRaw('country_id, country_code, COUNT(*) as cities_count')
            ->where('flag', 1)
            ->groupBy('country_id', 'country_code')
            ->orderBy('cities_count', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get cities within a bounding box (for map-based searches)
     */
    public function getCitiesInBoundingBox(float $minLat, float $maxLat, float $minLng, float $maxLng): Collection
    {
        return $this->model->whereBetween('latitude', [$minLat, $maxLat])
            ->whereBetween('longitude', [$minLng, $maxLng])
            ->where('flag', 1)
            ->orderBy('name')
            ->get();
    }
}
