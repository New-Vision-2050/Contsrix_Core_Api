<?php

declare(strict_types=1);

namespace Modules\Company\CompanyType\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Company\CompanyType\Database\factories\CompanyTypeFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Country\Models\Country;

//use BasePackage\Shared\Traits\HasTranslations;

class CompanyType extends Model
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
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): CompanyTypeFactory
    {
        return CompanyTypeFactory::new();
    }
    public function countries()
    {
        return $this->belongsToMany(Country::class, 'company_type_countries', 'company_type_id', 'country_id')
                    ->withPivot('status');
    }
}
