<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Company\CompanyCore\Database\factories\CompanyFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Company\CompanyField\Models\CompanyField;
use Modules\Company\CompanyType\Models\CompanyType;
use Modules\Company\CompanyRegistrationType\Models\CompanyRegistrationType;
use Modules\Country\Models\Country;
use Modules\User\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
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
class Company extends BaseTenant implements TenantWithDatabase , HasMedia
{
    use HasFactory;
    use BaseFilterable;
    use InteractsWithMedia;
    use HasDatabase, HasDomains;
    use UuidTrait;
    use HasScopedValidationRules;

    //use HasTranslations;
    // use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;
    protected $table = 'companies';
//    protected $connection = "mysql";



    protected $keyType = 'string';

    protected $fillable = [
        "id",
        'name',
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
            'name',
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
            "updated_at"
        ];
    }


    public function domains()
    {
        return $this->hasMany(config('tenancy.domain_model'), 'company_id');
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
        return $this->belongsTo(CompanyRegistrationType::class,'registration_type_id');
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


}
