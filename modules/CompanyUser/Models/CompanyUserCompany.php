<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Models;

use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\CompanyUser\Database\factories\CompanyUserFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Enum\CompanyUserStatus;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

//use BasePackage\Shared\Traits\HasTranslations;

class CompanyUserCompany extends Pivot
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use CustomBelongsToTenant;



    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    protected $table = "company_users_companies";

    public $incrementing = false;

    protected $keyType = 'string';
    protected $guarded = [];


    protected function role(): Attribute
    {
        return Attribute::make(
            get: fn($value) => CompanyUserRole::lang($value)
        );
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn($value) => CompanyUserStatus::lang($value)
        );
    }

    protected $casts = [
        'id' => 'string',
        "company_id" => "string",
        "global_company_user_id" => "string",
    ];

    protected static function newFactory(): CompanyUserFactory
    {
        return CompanyUserFactory::new();
    }


}
