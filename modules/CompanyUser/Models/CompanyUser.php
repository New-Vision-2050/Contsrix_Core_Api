<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Models;

use AjCastro\EagerLoadPivotRelations\EagerLoadPivotTrait;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Models\CompanyAddress;
use Modules\CompanyUser\Database\factories\CompanyUserFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Country\Models\Country;
use Modules\JobTitle\Models\JobTitle;
use Modules\Shared\Currency\Models\Currency;
use Modules\Shared\Language\Models\Language;
use Modules\Shared\TimeZone\Models\TimeZone;
use Modules\User\Models\User;
use Modules\UserInfo\BankAccount\Models\BankAccount;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;
use Modules\UserInfo\JobOffer\Models\JobOffer;
use Modules\UserInfo\EmploymentContract\Models\EmploymentContract;
use Modules\UserInfo\UserSalary\Models\UserSalary;
use Modules\UserInfo\UserAbout\Models\UserAbout;
use Modules\UserInfo\Contactinfo\Models\ContactInfo;
use Modules\UserInfo\Qualification\Models\Qualification;
use Modules\UserInfo\UserExperience\Models\UserExperience;
use Modules\UserInfo\UserEducationalCourse\Models\UserEducationalCourse;
use Modules\UserInfo\ProfessionalCertificate\Models\ProfessionalCertificate;
use Modules\UserInfo\UserPrivilege\Models\UserPrivilege;
use Modules\UserInfo\UserRelative\Models\UserRelative;
use Modules\UserInfo\ContractualRelationship\Models\ContractualRelationship;
use Stancl\Tenancy\Database\Concerns\BelongsToPrimaryModel;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

//use BasePackage\Shared\Traits\HasTranslations;

class CompanyUser extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use EagerLoadPivotTrait;
    use BelongsToPrimaryModel;
    use InteractsWithMedia;

    //use HasTranslations;
    use SoftDeletes;

    //public array $translatable = [];
    protected $with = ["users"];
    public $incrementing = false;

    protected $keyType = 'string';


    protected $fillable = [
        'name',
        "email",

        "country_id",
        "border_number",
        "residence",
        "passport",
        "identity",
        'job_title_id',
        "global_id",

        "other_phone",
        "code_other_phone",
        "address",
        "address_attendance",
        "nickname",
        "is_default",
        "birthdate_gregorian",
        "birthdate_hijri",
        "landline_number",
        "postal_code",

        "whatsapp",
        "facebook",
        "telegram",
        "instagram",
        "snapchat",
        "linkedin",

        'work_permit_start_date',
        'work_permit_end_date',
        'work_permit',

        'passport_start_date',
        'identity_start_date',
        'border_number_start_date',
        'entry_number_start_date',

        'passport_end_date',
        'identity_end_date',
        'border_number_end_date',
        'entry_number_end_date',
        'active_type',
        'active_date_to',
        'currency_id',
        'time_zone_id',
        'language_id',
    ];

    protected $casts = [
        'id' => 'string',
    ];


    public function companies()
    {
        return $this->hasManyThrough(
            Company::class,
            User::class,
            'global_company_user_id', // Foreign key on users table (intermediate)
            'id',                     // Foreign key on companies table
            'global_id',              // Local key on CompanyUser (this model)
            'company_id'              // Local key on intermediate users table
        )->where('companies.is_client', 0)->where('companies.is_active', 1)->where('companies.is_broker', 0)->where('companies.deleted_at',null)->withoutTenancy()->distinct();
    }
    public function clientCompanies()
    {
        return $this->hasManyThrough(
            Company::class,
            User::class,
            'global_company_user_id', // Foreign key on users table (intermediate)
            'id',                     // Foreign key on companies table
            'global_id',              // Local key on CompanyUser (this model)
            'company_id'              // Local key on intermediate users table
        )->where('companies.is_client', 1)->withoutTenancy()->distinct();
    }

    public function users()
    {
        return $this->hasMany(User::class, 'global_company_user_id', "global_id");
    }

    protected static function newFactory(): CompanyUserFactory
    {
        return CompanyUserFactory::new();
    }

    public function delete()
    {
        try {
            DB::beginTransaction();
//            $this->companies()->detach();
            $this->users()->delete();
            parent::delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            // Handle the exception
            throw new \Exception($e->getMessage(), 500);
        }
        return true;
    }

    public function rolesForCompany($companyId)
    {
        return $this->companies->where('id', $companyId)->sortByDesc('role')->pluck("pivot");
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function timeZone()
    {
        return $this->belongsTo(TimeZone::class);
    }

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function currency()
    {
        return $this->belongsTo(Country::class,'currency_id');
    }

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class)->withoutGlobalScope("active");
    }

    public function bankAccount()
    {
        return $this->hasOne(BankAccount::class, 'global_id', 'global_id')
            ->whereHas('bankTypeAccount', function ($q) {
                $q->where('code', 'default');
            });
    }

    public function getRelationshipToPrimaryModel(): string
    {
        return "users";
    }

    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $media->getFullUrl();
    }

    public function userProfessionalData()
    {
        return $this->hasOne(UserProfessionalData::class, 'global_id', 'global_id');
    }

    public function nationalAddress()
    {
        return $this->hasOne(CompanyUserAddress::class,"global_company_user_id","global_id");
    }

    public function jobOffer()
    {
        return $this->hasOne(JobOffer::class, 'global_id', 'global_id');
    }

    public function employmentContract()
    {
        return $this->hasOne(EmploymentContract::class, 'global_id', 'global_id');
    }

    public function userSalary()
    {
        return $this->hasOne(UserSalary::class, 'global_id', 'global_id');
    }

    public function userAbout()
    {
        return $this->hasOne(UserAbout::class, 'global_id', 'global_id');
    }

    public function contactInfo()
    {
        return $this->hasOne(ContactInfo::class, 'global_id', 'global_id');
    }

    public function qualifications()
    {
        return $this->hasMany(Qualification::class, 'global_id', 'global_id');
    }

    public function userExperiences()
    {
        return $this->hasMany(UserExperience::class, 'global_id', 'global_id');
    }

    public function userEducationalCourses()
    {
        return $this->hasMany(UserEducationalCourse::class, 'global_id', 'global_id');
    }

    public function professionalCertificates()
    {
        return $this->hasMany(ProfessionalCertificate::class, 'global_id', 'global_id');
    }

    public function userPrivileges()
    {
        return $this->hasMany(UserPrivilege::class, 'global_id', 'global_id');
    }

    public function userRelatives()
    {
        return $this->hasMany(UserRelative::class, 'global_id', 'global_id');
    }

    public function contractualRelationships()
    {
        return $this->hasMany(ContractualRelationship::class, 'global_id', 'global_id');
    }
}
