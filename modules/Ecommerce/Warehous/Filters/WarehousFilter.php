<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Warehous\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class WarehousFilter extends SearchModelFilter
{
    public $relations = ['company', 'country', 'city', 'products'];

    /**
     * Filter by warehouse name
     */
    public function name($name)
    {
        return $this->where('name', 'like', '%' . $name . '%');
    }

    /**
     * General search filter (searches in name, district, street)
     */
    public function search($search)
    {
        return $this->where(function ($query) use ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                  ->orWhere('district', 'like', '%' . $search . '%')
                  ->orWhere('street', 'like', '%' . $search . '%')
                  ->orWhereHas('company', function ($q) use ($search) {
                      $q->where('name', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('country', function ($q) use ($search) {
                      $q->where('name', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('city', function ($q) use ($search) {
                      $q->where('name', 'like', '%' . $search . '%');
                  });
        });
    }

    /**
     * Filter by company ID
     */
    public function companyId($companyId)
    {
        return $this->where('company_id', $companyId);
    }

    /**
     * Filter by multiple company IDs
     */
    public function companyIds($companyIds)
    {
        if (is_array($companyIds)) {
            return $this->whereIn('company_id', $companyIds);
        }
        return $this->where('company_id', $companyIds);
    }

    /**
     * Filter by company name
     */
    public function companyName($companyName)
    {
        return $this->whereHas('company', function ($q) use ($companyName) {
            $q->where('name', 'like', '%' . $companyName . '%');
        });
    }

    /**
     * Filter by country ID
     */
    public function countryId($countryId)
    {
        return $this->where('country_id', $countryId);
    }

    /**
     * Filter by multiple country IDs
     */
    public function countryIds($countryIds)
    {
        if (is_array($countryIds)) {
            return $this->whereIn('country_id', $countryIds);
        }
        return $this->where('country_id', $countryIds);
    }

    /**
     * Filter by country name
     */
    public function countryName($countryName)
    {
        return $this->whereHas('country', function ($q) use ($countryName) {
            $q->where('name', 'like', '%' . $countryName . '%');
        });
    }

    /**
     * Filter by city ID
     */
    public function cityId($cityId)
    {
        return $this->where('city_id', $cityId);
    }

    /**
     * Filter by multiple city IDs
     */
    public function cityIds($cityIds)
    {
        if (is_array($cityIds)) {
            return $this->whereIn('city_id', $cityIds);
        }
        return $this->where('city_id', $cityIds);
    }

    /**
     * Filter by city name
     */
    public function cityName($cityName)
    {
        return $this->whereHas('city', function ($q) use ($cityName) {
            $q->where('name', 'like', '%' . $cityName . '%');
        });
    }

    /**
     * Filter by district
     */
    public function district($district)
    {
        return $this->where('district', 'like', '%' . $district . '%');
    }

    /**
     * Filter by street
     */
    public function street($street)
    {
        return $this->where('street', 'like', '%' . $street . '%');
    }

    /**
     * Filter by active status
     */
    public function isActive($isActive = true)
    {
        return $this->where('is_active', (bool) $isActive);
    }

    /**
     * Filter by default warehouse
     */
    public function isDefault($isDefault = true)
    {
        return $this->where('is_default', (bool) $isDefault);
    }

    /**
     * Filter by location coordinates range
     */
    public function nearLocation($latitude, $longitude, $radiusKm = 10)
    {
        // Using Haversine formula for distance calculation
        return $this->whereRaw("
            (6371 * acos(
                cos(radians(?)) * 
                cos(radians(latitude)) * 
                cos(radians(longitude) - radians(?)) + 
                sin(radians(?)) * 
                sin(radians(latitude))
            )) <= ?
        ", [$latitude, $longitude, $latitude, $radiusKm]);
    }

    /**
     * Filter by latitude range
     */
    public function latitudeFrom($latitude)
    {
        return $this->where('latitude', '>=', $latitude);
    }

    public function latitudeTo($latitude)
    {
        return $this->where('latitude', '<=', $latitude);
    }

    /**
     * Filter by longitude range
     */
    public function longitudeFrom($longitude)
    {
        return $this->where('longitude', '>=', $longitude);
    }

    public function longitudeTo($longitude)
    {
        return $this->where('longitude', '<=', $longitude);
    }

    /**
     * Filter warehouses that have products
     */
    public function hasProducts($hasProducts = true)
    {
        if ($hasProducts) {
            return $this->whereHas('products');
        } else {
            return $this->whereDoesntHave('products');
        }
    }

    /**
     * Filter by minimum number of products
     */
    public function minProductsCount($count)
    {
        return $this->whereHas('products', function ($query) {
            // This will be handled by having clause in the service layer
        })->withCount('products')->having('products_count', '>=', $count);
    }

    /**
     * Filter by maximum number of products
     */
    public function maxProductsCount($count)
    {
        return $this->whereHas('products', function ($query) {
            // This will be handled by having clause in the service layer
        })->withCount('products')->having('products_count', '<=', $count);
    }

    /**
     * Advanced search with multiple criteria
     */
    public function advancedSearch($criteria)
    {
        return $this->where(function ($query) use ($criteria) {
            if (isset($criteria['name'])) {
                $query->where('name', 'like', '%' . $criteria['name'] . '%');
            }

            if (isset($criteria['company_id'])) {
                $query->where('company_id', $criteria['company_id']);
            }

            if (isset($criteria['country_id'])) {
                $query->where('country_id', $criteria['country_id']);
            }

            if (isset($criteria['city_id'])) {
                $query->where('city_id', $criteria['city_id']);
            }

            if (isset($criteria['district'])) {
                $query->where('district', 'like', '%' . $criteria['district'] . '%');
            }

            if (isset($criteria['street'])) {
                $query->where('street', 'like', '%' . $criteria['street'] . '%');
            }

            if (isset($criteria['is_active'])) {
                $query->where('is_active', (bool) $criteria['is_active']);
            }

            if (isset($criteria['is_default'])) {
                $query->where('is_default', (bool) $criteria['is_default']);
            }

            if (isset($criteria['has_products'])) {
                if ($criteria['has_products']) {
                    $query->whereHas('products');
                } else {
                    $query->whereDoesntHave('products');
                }
            }

            if (isset($criteria['location']) && isset($criteria['radius'])) {
                $lat = $criteria['location']['latitude'];
                $lng = $criteria['location']['longitude'];
                $radius = $criteria['radius'];
                
                $query->whereRaw("
                    (6371 * acos(
                        cos(radians(?)) * 
                        cos(radians(latitude)) * 
                        cos(radians(longitude) - radians(?)) + 
                        sin(radians(?)) * 
                        sin(radians(latitude))
                    )) <= ?
                ", [$lat, $lng, $lat, $radius]);
            }
        });
    }

    /**
     * Sort by name
     */
    public function sortByName($direction = 'asc')
    {
        return $this->orderBy('name', $direction);
    }

    /**
     * Sort by company name
     */
    public function sortByCompany($direction = 'asc')
    {
        return $this->join('companies', 'warehouses.company_id', '=', 'companies.id')
                    ->orderBy('companies.name', $direction);
    }

    /**
     * Sort by country name
     */
    public function sortByCountry($direction = 'asc')
    {
        return $this->join('countries', 'warehouses.country_id', '=', 'countries.id')
                    ->orderBy('countries.name', $direction);
    }

    /**
     * Sort by city name
     */
    public function sortByCity($direction = 'asc')
    {
        return $this->join('cities', 'warehouses.city_id', '=', 'cities.id')
                    ->orderBy('cities.name', $direction);
    }

    /**
     * Sort by products count
     */
    public function sortByProductsCount($direction = 'desc')
    {
        return $this->withCount('products')->orderBy('products_count', $direction);
    }

    /**
     * Sort by distance from a point
     */
    public function sortByDistance($latitude, $longitude, $direction = 'asc')
    {
        return $this->orderByRaw("
            (6371 * acos(
                cos(radians(?)) * 
                cos(radians(latitude)) * 
                cos(radians(longitude) - radians(?)) + 
                sin(radians(?)) * 
                sin(radians(latitude))
            )) {$direction}
        ", [$latitude, $longitude, $latitude]);
    }

    /**
     * Filter by date range
     */
    public function dateRange($from = null, $to = null, $field = 'created_at')
    {
        if ($from && $to) {
            return $this->whereBetween($field, [$from, $to]);
        } elseif ($from) {
            return $this->where($field, '>=', $from);
        } elseif ($to) {
            return $this->where($field, '<=', $to);
        }
        return $this;
    }

    /**
     * Filter by warehouses created in last X days
     */
    public function createdInLastDays($days)
    {
        return $this->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Filter by warehouses updated in last X days
     */
    public function updatedInLastDays($days)
    {
        return $this->where('updated_at', '>=', now()->subDays($days));
    }
}
