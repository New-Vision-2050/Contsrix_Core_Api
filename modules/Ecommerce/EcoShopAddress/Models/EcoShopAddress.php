<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShopAddress\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ecommerce\EcoShopAddress\Database\factories\EcoShopAddressFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Country\Models\City;
use Modules\Country\Models\Country;
use Modules\Shared\TimeZone\Models\TimeZone;

class EcoShopAddress extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',

        'country_id',
        'city_id',
        'time_zone_id',

        'district',
        'street',

        'building_number',
        'postal_code',

        'latitude',
        'longitude',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    protected static function newFactory(): EcoShopAddressFactory
    {
        return EcoShopAddressFactory::new();
    }
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id', 'id');
    }
    public function timeZone(): BelongsTo
    {
        return $this->belongsTo(TimeZone::class, 'time_zone_id', 'id');
    }
}
