<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\ProcedureSetting\Database\factories\ProcedureSettingFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;

class ProcedureSetting extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;

    protected $table = 'procedure_settings';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'type',
        'execute_type',
        'icon',
        'percentage',
        'deadline_days',
        'deadline_hours',
        'escalation_management_hierarchy_id',
        'company_id',
        'work_flow_id',
    ];

    protected $casts = [
        'id'         => 'string',
        'percentage' => 'float',
        'deadline_days' => 'integer',
        'deadline_hours' => 'integer',
        'escalation_management_hierarchy_id' => 'integer',
        'work_flow_id'                       => 'string',
    ];

    public function getRelationshipToPrimaryModel(): string
    {
        return 'company';
    }

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function steps()
    {
        return $this->hasMany(ProcedureSettingStep::class, 'procedure_setting_id');
    }

    public function escalationManagementHierarchy()
    {
        return $this->belongsTo(ManagementHierarchy::class, 'escalation_management_hierarchy_id');
    }

    public function workFlow()
    {
        return $this->belongsTo(WorkFlow::class, 'work_flow_id');
    }

    protected static function newFactory(): ProcedureSettingFactory
    {
        return ProcedureSettingFactory::new();
    }
}
