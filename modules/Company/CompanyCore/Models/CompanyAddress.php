<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Models;

use BasePackage\Shared\Traits\HasTranslations;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\AdminRequest\Models\AdminRequest;
use Modules\Company\CompanyCore\Database\factories\CompanyFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Company\CompanyField\Models\CompanyField;
use Modules\Company\CompanyType\Models\CompanyType;
use Modules\Company\CompanyRegistrationType\Models\CompanyRegistrationType;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Country\Models\Country;
use Modules\User\Models\User;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Database\Concerns\BelongsToPrimaryModel;


class CompanyAddress extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use BelongsToPrimaryModel;
//    use InteractsWithMedia;
//    use HasTranslations;

    // use SoftDeletes;

//    public array $translatable = ["name"];


    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        "company_id",
        "country_id",
        "city_id",
        "state_id",
        "neighborhood_name",
        "street_name",
        "building_number",
        "additional_phone",
        "postal_code",

    ];
    protected $casts = [
        'id' => 'string',
        'date_activate' => 'date'
    ];
    public function getMediaUrlsAttribute()
    {
        return $this->media->map(fn($media) => $media->getFullUrl());
    }
    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }
    public function getRelationshipToPrimaryModel(): string
    {
        return "company";
    }


}
