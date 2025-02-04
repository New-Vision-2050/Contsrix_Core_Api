<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\CompanyUser\Database\factories\CompanyUserFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Enum\CompanyUserStatus;

//use BasePackage\Shared\Traits\HasTranslations;

class CompanyUserCompany extends Pivot
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    protected $table ="company_users_companies";

    public $incrementing = false;

    protected $keyType = 'string';
    protected $guarded=[];


    protected $casts = [
        'id' => 'string',
        "role"=>CompanyUserRole::class,
        "status"=>CompanyUserStatus::class
    ];


    protected static function newFactory(): CompanyUserFactory
    {
        return CompanyUserFactory::new();
    }


}
