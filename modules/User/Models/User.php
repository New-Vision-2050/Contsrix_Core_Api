<?php

declare(strict_types=1);

namespace Modules\User\Models;

use App\Casts\UuidCast;

use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\HasTranslations;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\CompanyUser\Models\CompanyUserCompany;
use Modules\CompanyUser\Models\CompanyUserCompanyManagementHierarchy;
use Modules\Setting\Models\LoginWay;
use Modules\User\Database\factories\UserFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;


//use BasePackage\Shared\Traits\HasTranslations;

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
        "status"
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
            "phone_code",
            "company",
            'updated_at',
            'created_at'
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
        return $this->belongsTo(Company::class);
    }

    public function companyUser()
    {
        return $this->belongsTo(CompanyUser::class , 'global_company_user_id' , 'global_id' );
    }

    public function companyUserCompanies()
    {
        return $this->hasMany(CompanyUserCompany::class,"global_company_user_id" , "global_company_user_id" );
    }

    public function roleAndBranches()
    {
        return $this->hasMany(CompanyUserCompanyManagementHierarchy::class,"user_id","id");
    }


    public function managementHierarchies()
    {
        return $this->hasManyThrough(ManagementHierarchy::class,
            CompanyUserCompanyManagementHierarchy::class,
            'user_id',
            'id',
            'id',
            'management_hierarchy_id'
        )->distinct();
    }
}
