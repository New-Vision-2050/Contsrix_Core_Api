<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Project\ProjectManagement\Database\factories\ProjectManagementFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Project\ProjectType\Models\ProjectType;
use Modules\Shared\Currency\Models\Currency;
use Modules\User\Models\User;
use Modules\Company\CompanyCore\Models\Company;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ProjectManagement extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;

    protected $table = 'projects';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'project_type_id',
        'sub_project_type_id',
        'sub_sub_project_type_id',
        'name',
        'manager_id',
        'branch_id',
        'project_owner_type',
        'project_owner_id',
        'contract_id',
        'client_id',
        'project_classification_id',
        'cost_center_branch_id',
        'management_id',
        'currency_id',
        'project_value',
        'company_id',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'project_type_id' => 'integer',
        'sub_project_type_id' => 'integer',
        'sub_sub_project_type_id' => 'integer',
        'manager_id' => 'string',
        'branch_id' => 'integer',
        'project_owner_type' => 'string',
        'project_owner_id' => 'string',
        'contract_id' => 'string',
        'client_id' => 'string',
        'project_classification_id' => 'string',
        'cost_center_branch_id' => 'string',
        'management_id' => 'string',
        'currency_id' => 'string',
        'company_id' => 'string',
        'project_value' => 'decimal:2',
        'status' => 'integer',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    // Relationships
    public function projectType()
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }

    public function subProjectType()
    {
        return $this->belongsTo(ProjectType::class, 'sub_project_type_id');
    }

    public function subSubProjectType()
    {
        return $this->belongsTo(ProjectType::class, 'sub_sub_project_type_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function branch()
    {
        return $this->belongsTo(ManagementHierarchy::class, 'branch_id');
    }

    public function projectOwner()
    {
        return $this->morphTo('project_owner', 'project_owner_type', 'project_owner_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function costCenterBranch()
    {
        return $this->belongsTo(ManagementHierarchy::class, 'cost_center_branch_id');
    }

    public function management()
    {
        return $this->belongsTo(ManagementHierarchy::class, 'management_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    protected static function newFactory(): ProjectManagementFactory
    {
        return ProjectManagementFactory::new();
    }
}
