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
use Modules\CompanyUser\Enum\CompanyUserRole;

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
        "country_id",
        "border_number",
        "residence",
        "passport",
        "identity",
    ];

    protected $casts = [
        'id' => 'string',
    ];


    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_users_companies', 'company_user_id', 'company_id')
            ->withPivot('role','status');
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
        return $this->companies->where('id',$companyId)->pluck("pivot");
    }
}
