<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Models;

use AjCastro\EagerLoadPivotRelations\EagerLoadPivotTrait;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Database\factories\CompanyUserFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Country\Models\Country;
use Modules\JobTitle\Models\JobTitle;
use Modules\Shared\Currency\Models\Currency;
use Modules\Shared\Language\Models\Language;
use Modules\Shared\TimeZone\Models\TimeZone;
use Modules\User\Models\User;

//use BasePackage\Shared\Traits\HasTranslations;

class CompanyUser extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use EagerLoadPivotTrait;

    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';


    protected $fillable = [
        'name',
        "email",
        "phone",
        "phone_code",
        "country_id",
        "border_number",
        "residence",
        "passport",
        "identity",
        'job_title_id',
        "global_id",
    ];

    protected $casts = [
        'id' => 'string',
    ];


    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_users_companies', 'global_company_user_id', 'company_id')
            ->withPivot('role','status');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'global_company_user_id',"global_id");
    }


    protected static function newFactory(): CompanyUserFactory
    {
        return CompanyUserFactory::new();
    }

    public function delete()
    {
        try {
            DB::beginTransaction();
            $this->companies()->detach();
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
        return $this->companies->where('id',$companyId)->sortByDesc('role')->pluck("pivot");
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
        return $this->belongsTo(Currency::class);
    }
    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class);
    }
}
