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
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Modules\Shared\Media\MediaLibrary\CustomPathGenerator;
use Stancl\Tenancy\Database\Concerns\HasScopedValidationRules;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\DatabaseConfig;

//use BasePackage\Shared\Traits\HasTranslations;


/**
 * @method  __call(string $method, array $parameters)
 * @method  __callStatic(string $method, array $parameters)
 */
class Company extends BaseTenant implements TenantWithDatabase, HasMedia
{
    use HasFactory;
    use BaseFilterable;
    use InteractsWithMedia;
    use HasTranslations;

    use HasDatabase, HasDomains;
    use UuidTrait;
    use HasScopedValidationRules;


    public array $translatable = ["name"];

    protected $with = ['country', 'companyType', 'companyField', 'companyRegistrationType', 'generalManager', "mainBranch"];

    public $incrementing = false;
    protected $table = 'companies';
//    protected $connection = "mysql";


    protected $keyType = 'string';

    protected $fillable = [
        "name",
        "id",
        'user_name',
        'email',
        'phone',
        'country_id',
        'company_type_id',
        'company_field_id',
        'registration_type_id',
        'general_manager_id',
        'is_active',
        'complete_data',
        'date_activate',
        'registration_no',
        'registration_no_start_date',
        'registration_no_end_date',
        'serial_no',
        'image_path'
    ];
    protected $casts = [
        'id' => 'string',
        'date_activate' => 'date'
    ];

    public static function getCustomColumns(): array
    {
        return [
            "id",
            'user_name',
            'email',
            'phone',
            'country_id',
            'company_type_id',
            'company_field_id',
            'registration_type_id',
            'general_manager_id',
            'is_active',
            'complete_data',
            'date_activate',
            'registration_no',
            'serial_no',
            'image_path',
            "created_at",
            "updated_at",
            "is_central_company"
        ];
    }


    public function domains()
    {
        return $this->hasMany(config('tenancy.domain_model'), 'company_id');
    }

    public function getMediaUrlsAttribute()
    {
        return $this->media->map(fn($media) => $media->getFullUrl());
    }

    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function generalManager()
    {
        return $this->belongsTo(User::class, 'general_manager_id', 'id');
    }

    public function companyType()
    {
        return $this->belongsTo(CompanyType::class);
    }

    public function companyField()
    {
        return $this->belongsTo(CompanyField::class);
    }

    public function companyRegistrationType()
    {
        return $this->belongsTo(CompanyRegistrationType::class, 'registration_type_id');
    }

    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $media->getFullUrl(); // Ensure this is using your custom method
    }

    public function getTenantKeyName(): string
    {
        return 'id';
    }

    public function getTenantKey()
    {
        return $this->getAttribute($this->getTenantKeyName());
    }


    public function adminRequestTransaction()
    {
        return $this->morphMany(AdminRequest::class, 'requestable');
    }

    public function mainBranch()
    {
        return $this->hasOne(ManagementHierarchy::class, 'company_id')->where('parent_id', null)->where('type', 'branch');
    }

    public function companyAddress()
    {
        return $this->hasOne(CompanyAddress::class, 'company_id');
    }

}
