<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Warehous\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ecommerce\Warehous\Database\factories\WarehousFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Country\Models\City;
use Modules\Country\Models\Country;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;

//use BasePackage\Shared\Traits\HasTranslations;

class Warehous extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'company_id',
        'is_default',
        'country_id',
        'city_id',
        'district',
        'street',
        'latitude',
        'longitude',
        'is_active'
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): WarehousFactory
    {
        return WarehousFactory::new();
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
    public function products()
    {
        return $this->hasMany(EcoProduct::class,'warehouse_id');
    }
}
