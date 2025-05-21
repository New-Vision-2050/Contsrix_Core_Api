<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Models;

use Modules\Country\Models\City;
use Modules\Country\Models\State;
use Modules\Country\Models\Country;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
// use BasePackage\Shared\Traits\HasTranslations;

class CompanyUserAddress extends Model
{
    use UuidTrait;
    use BaseFilterable;
    // use HasTranslations;
    // use SoftDeletes;

    public array $translatable = [];
    protected $table = 'company_user_address';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        "country_id",
        "city_id",
        "state_id",
        "neighborhood_name",
        "street_name",
        "building_number",
        "additional_phone",
        "postal_code",
        "global_company_user_id",
        "latitude",
        "longitude"

    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
