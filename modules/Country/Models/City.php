<?php

declare(strict_types=1);

namespace Modules\Country\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Country\Database\factories\CountryFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Setting\Models\Driver;

//use BasePackage\Shared\Traits\HasTranslations;

class City extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;

    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $table = 'cities';

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'state_id',
        'state_code',
        'country_id',
        'country_code',
        'latitude',
        'longitude',
        'flag',
        'wikiDataId'
    ];

    protected $casts = [
        'id' => 'string',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'flag' => 'integer'
    ];

    /**
     * Get the state that the city belongs to
     */
    public function state()
    {
        return $this->belongsTo(State::class, "state_id");
    }

    /**
     * Get the country that the city belongs to
     */
    public function country()
    {
        return $this->belongsTo(Country::class, "country_id");
    }

    /**
     * Get the full address string
     */
    public function getFullAddressAttribute(): string
    {
        $address = $this->name;
        
        if ($this->state) {
            $address .= ', ' . $this->state->name;
        }
        
        if ($this->country) {
            $address .= ', ' . $this->country->name;
        }
        
        return $address;
    }

    /**
     * Get coordinates as array
     */
    public function getCoordinatesAttribute(): array
    {
        return [
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude
        ];
    }

    /**
     * Scope to filter active cities
     */
    public function scopeActive($query)
    {
        return $query->where('flag', 1);
    }

    /**
     * Scope to filter by country
     */
    public function scopeByCountry($query, $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    /**
     * Scope to filter by state
     */
    public function scopeByState($query, $stateId)
    {
        return $query->where('state_id', $stateId);
    }

    /**
     * Calculate distance to another city or coordinates
     */
    public function distanceTo($latitude, $longitude): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers
        
        $latFrom = deg2rad((float) $this->latitude);
        $lonFrom = deg2rad((float) $this->longitude);
        $latTo = deg2rad((float) $latitude);
        $lonTo = deg2rad((float) $longitude);
        
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;
        
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
}
