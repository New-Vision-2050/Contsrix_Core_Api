<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Models;

use Modules\User\Models\User;
use Modules\WebsiteCMS\WebsiteTheme\Models\WebsiteTheme;
use OwenIt\Auditing\Contracts\Auditable;
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
use Modules\Company\CompanyField\Models\CompanyField;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Modules\Shared\Media\MediaLibrary\CustomPathGenerator;
use Stancl\Tenancy\Database\Concerns\HasScopedValidationRules;
use Modules\Company\CompanyCore\Database\factories\CompanyFactory;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Company\CompanyRegistrationType\Models\CompanyRegistrationType;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

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
 * @property int $is_client Path to the company's image.
 *
 * @method static CompanyFactory factory(...$parameters)
 * @method  __call(string $method, array $parameters)
 * @method  __callStatic(string $method, array $parameters)
 */
class Company extends BaseTenant implements TenantWithDatabase, HasMedia, Auditable
{
    use HasFactory;
    use UuidTrait;
    use HasDatabase;
    use HasDomains;
    use InteractsWithMedia;
    use BaseFilterable;
    use SoftDeletes;
    use HasScopedValidationRules;
    use CustomBelongsToTenant;
    use HasRelationships;
    use HasTranslations;
    use \OwenIt\Auditing\Auditable;


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
        'image_path',
        "is_client",
        "is_broker"
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
            "is_client",
            "is_broker"
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
            // Skip if serial_no is already set
            if (!empty($model->serial_no)) {
                return;
            }

            // Generate a UUID-based serial number that is guaranteed to be unique
            // Format: CX-{first 8 chars of UUID}
            $uuid = \Illuminate\Support\Str::uuid()->toString();
            $shortUuid = substr($uuid, 0, 8); // Take first 8 characters of the UUID

            // Generate the serial number with a prefix
            $model->serial_no = strtoupper($model->user_name) ?? "CX";
            $model->serial_no .= "-" . $shortUuid;

            // Double-check that this serial number doesn't already exist (extremely unlikely but possible)
            // If it does, generate a new one
            $attempts = 0;
            $maxAttempts = 5;

            while (\Illuminate\Support\Facades\DB::table('companies')->where('serial_no', $model->serial_no)->exists()) {
                if ($attempts >= $maxAttempts) {
                    // If we've tried too many times, use a timestamp-based fallback
                    $timestamp = time();
                    $model->serial_no = strtoupper($model->user_name)?? "CX";
                    $model->serial_no .= dechex($timestamp);
                    break;
                }

                // Generate a new UUID and try again
                $uuid = \Illuminate\Support\Str::uuid()->toString();
                $shortUuid = substr($uuid, 0, 8);
                $model->serial_no = strtoupper($model->user_name) ?? "CX";
                $model->serial_no .= "-" . $shortUuid;
                $attempts++;
            }
        });
    }

    public function packages(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \Modules\Subscription\Package\Models\Package::class,
            'company_package',
            'company_id',
            'package_id'
        )
//            ->using(CompanyPackagePivot::class)
            ->withPivot(['subscribed_at', 'expires_at', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Get the permission limits for this company.
     */
    public function permissionLimits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\Modules\Subscription\Package\Models\CompanyPermissionLimit::class, 'company_id');
    }

    /**
     * Deep relationship: Get CompanyAccessPrograms through packages
     * This gets all CompanyAccessPrograms that this company has access to through its packages
     */
    public function companyAccessPrograms()
    {
        return $this->hasManyDeepFromRelations(
            $this->packages(),
            (new \Modules\Subscription\Package\Models\Package())->companyAccessProgram()
        );
    }

    /**
     * Alternative deep relationship using table names and foreign keys
     * This is more explicit about the path through the database
     */
    public function companyAccessProgramsDeep()
    {
        return $this->hasManyDeep(
            \Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram::class,
            ['company_package', 'packages'], // intermediate tables
            ['company_id', 'package_id'], // foreign keys on intermediate tables
            ['id', 'id'], // local keys on intermediate tables
            ['id', 'company_access_program_id'] // foreign keys on related tables
        );
    }


    public function websiteTheme()
    {
        return $this->hasOne(WebsiteTheme::class,"company_id");

    }

    /**
     * Get distinct CompanyAccessPrograms through packages (removes duplicates)
     */
    public function distinctCompanyAccessPrograms()
    {
        return $this->hasManyDeepFromRelations(
            $this->packages(),
            (new \Modules\Subscription\Package\Models\Package())->companyAccessProgram()
        )->distinct();
    }
}
