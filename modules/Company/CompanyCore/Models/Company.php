<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Models;

use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\HasTranslations;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
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
 * Class Company
 *
 * Represents a company entity in the system.
 *
 * @property string $id Unique identifier for the company.
 * @property string $name Name of the company.
 * @property string $user_name Username associated with the company.
 * @property string $email Email address of the company.
 * @property string $phone Phone number of the company.
 * @property string $country_id ID of the country the company is located in.
 * @property string $company_type_id ID representing the type of the company.
 * @property string $company_field_id ID representing the field of the company.
 * @property string $general_manager_id ID of the general manager of the company.
 * @property bool $is_active Indicates if the company is active.
 * @property bool $complete_data Indicates if all required data for the company is complete.
 * @property string $date_activate Date when the company was activated.
 * @property string $serial_no Serial number associated with the company.
 * @property string $image_path Path to the company's image.
 *
 * @method static CompanyFactory factory(...$parameters)
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
        return $this->hasOne(User::class)->where("is_owner",1);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            // Skip if serial_no is already set
            if (!empty($model->serial_no)) {
                return;
            }
            
            // Use a database transaction to ensure atomicity
            DB::transaction(function() use ($model) {
                // Lock the companies table for update to prevent race conditions
                DB::statement('LOCK TABLES companies WRITE');
                
                try {
                    // Get the highest serial number using numeric extraction
                    $lastSerial = self::where('serial_no', 'LIKE', 'CX-%')
                        ->orderByRaw('CAST(SUBSTRING(serial_no, 4) AS UNSIGNED) DESC')
                        ->value('serial_no');
                    
                    // Extract the number and increment it
                    $lastNumber = $lastSerial ? (int)substr($lastSerial, 3) : 0;
                    $newNumber = $lastNumber + 1;
                    
                    // Generate a new serial number
                    $serial = 'CX-' . $newNumber;
                    
                    // Assign the serial number to the model
                    $model->serial_no = $serial;
                } finally {
                    // Always unlock tables
                    DB::statement('UNLOCK TABLES');
                }
            }, 5); // 5 retries
        });
    }
}
