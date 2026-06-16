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
        'sort_order',
        'parent_id',
        'form',
        'conditions',
        'appears_before_id',
        'appears_after_id',
        'is_active',
    ];

    protected $casts = [
        'id'         => 'string',
        'percentage' => 'float',
        'deadline_days' => 'integer',
        'deadline_hours' => 'integer',
        'escalation_management_hierarchy_id' => 'integer',
        'work_flow_id'                       => 'string',
        'sort_order'                         => 'integer',
        'conditions'                         => 'array',
        'is_active'                          => 'int',
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

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function internalProcedures()
    {
        return $this->hasMany(self::class, 'parent_id')
            ->whereNotNull('form')
            ->orderBy('sort_order');
    }

    public function procedureSettings()
    {
        return $this->hasMany(self::class, 'parent_id')
            ->whereNotNull('form');
    }

    public function appearsBeforeEntry()
    {
        return $this->belongsTo(self::class, 'appears_before_id');
    }

    public function appearsAfterEntry()
    {
        return $this->belongsTo(self::class, 'appears_after_id');
    }

    public function isInternalProcedure(): bool
    {
        return $this->parent_id !== null && $this->form !== null;
    }

    protected static function newFactory(): ProcedureSettingFactory
    {
        return ProcedureSettingFactory::new();
    }
}
