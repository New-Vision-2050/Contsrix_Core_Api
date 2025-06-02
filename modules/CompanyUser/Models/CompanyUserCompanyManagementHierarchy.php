<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\User\Models\User;
use Stancl\Tenancy\Database\Concerns\BelongsToPrimaryModel;

// use BasePackage\Shared\Traits\HasTranslations;

class CompanyUserCompanyManagementHierarchy extends Model
{
    use UuidTrait;
    use BaseFilterable;
    use BelongsToPrimaryModel;

    // use HasTranslations;
    // use SoftDeletes;
    protected $table = "company_users_company_management_hierarchies";
    public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'management_hierarchy_id',
        'company_user_company_id',
        "user_id"

    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);

    }

    public function companyUserCompany()
    {
        return $this->belongsTo(CompanyUserCompany::class);
    }

    public function managementHierarchy()
    {
        return $this->belongsTo(ManagementHierarchy::class);
    }

    public function getRelationshipToPrimaryModel(): string
    {
        return "user";
    }
}
