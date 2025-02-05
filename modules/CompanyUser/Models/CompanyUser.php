<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Models;

use AjCastro\EagerLoadPivotRelations\EagerLoadPivotTrait;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Company\Models\Company;
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
        return $this->belongsToMany(Company::class,"company_users_companies","company_id","company_user_id")
            ->using(CompanyUserCompany::class);
    }


    protected static function newFactory(): CompanyUserFactory
    {
        return CompanyUserFactory::new();
    }
}
