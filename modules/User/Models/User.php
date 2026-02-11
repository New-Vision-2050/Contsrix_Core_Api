<?php

declare(strict_types=1);

namespace Modules\User\Models;

use App\Casts\UuidCast;

use Modules\CompanyUser\Models\BrokerDetail;
use Modules\CompanyUser\Models\ClientDetail;
use Modules\Setting\Models\LoginWay;
use App\Traits\CustomBelongsToTenant;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\Contracts\Auditable;
use Modules\CompanyUser\Models\CompanyUser;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\SoftDeletes;
use BasePackage\Shared\Traits\HasTranslations;
use Modules\SubEntity\Models\RegistrationForm;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\CompanyUser\Models\CompanyUserCompany;
use Modules\CompanyUser\Models\CompanyUserCompanyManagementHierarchy;
use Modules\CompanyUser\Enum\CompanyUserRole;

use Modules\User\Database\factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;

//use BasePackage\Shared\Traits\HasTranslations;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property-read CompanyUser|null $companyUser
 * @property-read UserProfessionalData|null $professionalData
 */
class User extends Authenticatable implements JWTSubject, Auditable
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use Notifiable;
    use HasTranslations;
    use HasRoles;
    use \OwenIt\Auditing\Auditable;
    use CustomBelongsToTenant;
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;



    use SoftDeletes;

    //    public array $translatable = [];
    protected $primaryKey = "id";
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        "phone_code",
        "login_way_id",
        "global_company_user_id",
        "company_id",
        "is_owner",
        "management_hierarchy_id",
        "status",
        "message_address",
        "fcm_token"
    ];

    protected $casts = [
        'id' => UuidCast::class,
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get attributes available for sub-entities excluding sensitive fields (like password).
     *
     * @return array
     * @todo create an interface & trait
     */
    public static function getSubEntitiesAvailableAttributes()
    {
        return [
            'name',
            'email',
            'phone',
            "companies",
            'user-type',
            "data_status",
            "branch",
            "job_title",
            "residence",
            "broker",
            "number_of_projects",
            "end_date",
        ];
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function LoginWay()
    {
        return $this->belongsTo(LoginWay::class, 'login_way_id');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function company()
    {
        return $this->belongsTo(Company::class,"company_id","id");
    }

    public function companyUser()
    {
        return $this->belongsTo(CompanyUser::class, 'global_company_user_id', 'global_id');
    }

    public function companyUserCompanies()
    {
        return $this->hasMany(CompanyUserCompany::class, "global_company_user_id", "global_company_user_id");
    }

    public function roleAndBranches()
    {
        return $this->hasMany(CompanyUserCompanyManagementHierarchy::class, "user_id", "id");
    }


    /**
     * Get the unique management hierarchies associated with the user through the roleAndBranches relation.
     * Filtered by the role column in CompanyUserCompany model.
     *
     * @param string|int|null $role The role value to filter by (optional)
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function managementHierarchies($role = null)
    {
        $query = $this->hasManyThrough(
            ManagementHierarchy::class,
            CompanyUserCompanyManagementHierarchy::class,
            'user_id', // Foreign key on CompanyUserCompanyManagementHierarchy table
            'id', // Foreign key on ManagementHierarchy table
            'id', // Local key on User table
            'management_hierarchy_id' // Local key on CompanyUserCompanyManagementHierarchy table
        )
            ->join('company_users_companies', 'company_users_company_management_hierarchies.company_user_company_id', '=', 'company_users_companies.id')
            ->select('management_hierarchies.*')
            ->distinct();

        // Apply role filter if provided
        if ($role !== null) {
            $query->where('company_users_companies.role', $role);
        }

        return $query;
    }




    public function registrationForm()
    {
        return $this->belongsTo(RegistrationForm::class);
    }

    public function clientDetail()
    {
        return $this->hasOne(ClientDetail::class);
    }

    public function branch()
    {
        return $this->belongsTo(ManagementHierarchy::class, 'management_hierarchy_id')
            ->where('type','branch');
    }

    public function managementHierarchy()
    {
        return $this->belongsTo(ManagementHierarchy::class, 'management_hierarchy_id');
    }

    public function userProfessionalData()
    {
        return $this->hasOne(UserProfessionalData::class, 'global_id', 'global_company_user_id')->where("company_id", "=", tenant("id"));
    }
    public function professionalData()
    {
        return $this->hasOne(UserProfessionalData::class, 'user_id', 'id')->withoutTenancy();
    }
    /**
     * Get all companies for this user using hasManyThrough relationship
     * User -> CompanyUserCompany (pivot) -> Company
     */
    public function companies()
    {
        return $this->hasManyThrough(
            Company::class,
            CompanyUserCompany::class,
            'global_company_user_id', // Foreign key on pivot table
            'id',                     // Foreign key on Company table
            'global_company_user_id', // Local key on User table
            'company_id'              // Local key on pivot table
        )->withoutTenancy()->distinct();
    }

    public function clientCompanies()
    {
        return $this->hasManyThrough(
            Company::class,
            User::class,
            'global_company_user_id', // Foreign key on users table (intermediate)
            'id',                     // Foreign key on companies table
            'global_company_user_id', // Local key on this user
            'company_id'              // Local key on intermediate users table
        )->where('companies.is_client', 1)->withoutTenancy()->distinct();
    }


    public function brokerDetail()
    {
        return $this->hasOne(BrokerDetail::class);
    }

    /**
     * Get all favourite files for this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function favouriteFiles()
    {
        return $this->belongsToMany(
            \Modules\ArchiveLibrary\File\Models\File::class,
            'users_file_favourites',
            'user_id',
            'file_id'
        )->withTimestamps();
    }


}
