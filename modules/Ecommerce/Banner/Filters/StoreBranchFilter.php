<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Filters;

use Illuminate\Database\Eloquent\Builder;
use BasePackage\Shared\Filters\SearchModelFilter;

class StoreBranchFilter extends SearchModelFilter

{
    public $relations = ['company', 'country'];

    public function type(string $type)
    {
        return $this->where('type', $type);
    }

    public function countryId(string $countryId)
    {
        return $this->where('country_id', $countryId);
    }

    public function isActive(bool $isActive)
    {
        return $this->where('is_active', $isActive);
    }

    public function companyId(string $companyId)
    {
        return $this->where('company_id', $companyId);
    }

    public function search(string $search)
    {
        return $this->where(function (Builder $query) use ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                  ->orWhere('address', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
        });
    }

    public function name(string $name)
    {
        return $this->where('name', 'like', '%' . $name . '%');
    }

    public function address(string $address)
    {
        return $this->where('address', 'like', '%' . $address . '%');
    }

    public function phone(string $phone)
    {
        return $this->where('phone', 'like', '%' . $phone . '%');
    }

    public function email(string $email)
    {
        return $this->where('email', 'like', '%' . $email . '%');
    }

    public function latitude(float $latitude)
    {
        return $this->where('latitude', $latitude);
    }

    public function longitude(float $longitude)
    {
        return $this->where('longitude', $longitude);
    }

    public function hasLocation()
    {
        return $this->whereNotNull('latitude')
                            ->whereNotNull('longitude');
    }

    public function withinRadius(float $centerLat, float $centerLng, float $radiusKm)
    {
        // Using Haversine formula to find branches within radius
        return $this->whereRaw(
            '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= ?',
            [$centerLat, $centerLng, $centerLat, $radiusKm]
        );
    }

    public function orderByDistance(float $centerLat, float $centerLng)
    {
        // Order by distance from center point
        return $this->orderByRaw(
            '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))',
            [$centerLat, $centerLng, $centerLat]
        );
    }

    public function createdAfter(string $date)
    {
        return $this->where('created_at', '>=', $date);
    }

    public function createdBefore(string $date)
    {
        return $this->where('created_at', '<=', $date);
    }

    public function updatedAfter(string $date)
    {
        return $this->where('updated_at', '>=', $date);
    }

    public function updatedBefore(string $date)
    {
        return $this->where('updated_at', '<=', $date);
    }
}
