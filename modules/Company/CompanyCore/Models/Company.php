<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Models;

use Modules\User\Models\User;
use Spatie\MediaLibrary\HasMedia;
use Stancl\Tenancy\DatabaseConfig;
use Modules\Country\Models\Country;
use App\Traits\CustomBelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\UuidTrait;
use Spatie\MediaLibrary\InteractsWithMedia;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\AdminRequest\Models\AdminRequest;
use BasePackage\Shared\Traits\HasTranslations;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Modules\Company\CompanyType\Models\CompanyType;
use Modules\Subscription\Models\CompanyPackagePivot;
use Modules\Company\CompanyField\Models\CompanyField;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Modules\Shared\Media\MediaLibrary\CustomPathGenerator;
use Stancl\Tenancy\Database\Concerns\HasScopedValidationRules;
use Modules\Company\CompanyCore\Database\factories\CompanyFactory;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Company\CompanyRegistrationType\Models\CompanyRegistrationType;

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
    use CustomBelongsToTenant;
    use softDeletes;


    public array $translatable = ["name"];

    //    protected $with = ['country', 'companyType', 'companyField', 'companyRegistrationType', 'generalManager', "mainBranch", "companyLegalData.media", "companyOfficialDocuments.media", "companyOfficialDocuments.activityLogs", "companyAddress","owner"];

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
        'general_manager_id',
        'is_active',
        'complete_data',
        'date_activate',
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
            // 'company_field_id',
            'general_manager_id',
            'is_active',
            'complete_data',
            'date_activate',
            'serial_no',
            'image_path',
            "created_at",
            "updated_at",
            "is_central_company",
            "check_activity",
            "registration_type_id",
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
        return $this->belongsTo(User::class, 'general_manager_id', 'id')->withoutTenancy();
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

    public function branches()
    {
        return $this->hasMany(ManagementHierarchy::class, 'company_id')->where('type', 'branch')->orderByRaw('is_main = ? DESC', [1]);
    }

    public function managements()
    {
        return $this->hasMany(ManagementHierarchy::class, 'company_id')->where('type', 'management');
    }

    public function firstBranch()
    {
        return $this->hasOne(ManagementHierarchy::class, 'company_id')->where('is_first_branch', true)->where('type', 'branch');
    }

    public function companyAddress()
    {
        return $this->hasOne(CompanyAddress::class, 'company_id');

    }

    public function companyLegalData()
    {
        return $this->hasMany(CompanyLegalData::class, 'company_id');

    }

    public function companyOfficialDocuments()
    {
        return $this->hasMany(CompanyOfficialDocument::class, 'company_id');
    }
    public function companyFields()
    {
        return $this->belongsToMany(CompanyField::class, 'company_company_fields', 'company_id', 'company_field_id');
    }

    public function owner()
    {
        return $this->hasOne(User::class)->where("is_owner", 1);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            do {
                $lastCode = self::where('serial_no', 'LIKE', 'CX-%')
                    ->orderByDesc('created_at')
                    ->value('serial_no');

                $newNumber = $lastCode ? (int) str_replace('CX-', '', $lastCode) + 1 : 1;
                $serial = 'CX-' . $newNumber;
            } while (self::where('serial_no', $serial)->exists());

            $model->serial_no = $serial;
        });
    }

    public function packages(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \Modules\Subscription\Models\Package::class,
            'company_package',
            'company_id',
            'package_id'
        )
            ->using(CompanyPackagePivot::class)
            ->withPivot(['subscribed_at', 'expires_at', 'is_active'])
            ->withTimestamps();
    }
}
