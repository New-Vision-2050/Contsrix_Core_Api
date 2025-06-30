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
use Modules\Company\ManagementHierarchy\Models\Branch;
use Modules\Country\Models\City;
use Modules\Country\Models\Country;
use Modules\Country\Models\State;
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

    protected $table = 'company_address';

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
        "management_hierarchy_id",
        "branch_id"

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

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class, "branch_id", "id");
    }

    public function getRelationshipToPrimaryModel(): string
    {
        return "company";
    }

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
