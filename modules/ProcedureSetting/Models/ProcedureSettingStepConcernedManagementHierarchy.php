<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;

class ProcedureSettingStepConcernedManagementHierarchy extends Model
{
    protected $table = 'procedure_setting_step_concerned_management_hierarchies';

    protected $fillable = [
        'procedure_setting_step_id',
        'management_hierarchy_id',
        'company_id',
    ];

    protected $casts = [
        'procedure_setting_step_id' => 'integer',
        'management_hierarchy_id'   => 'integer',
    ];

    public function step(): BelongsTo
    {
        return $this->belongsTo(ProcedureSettingStep::class, 'procedure_setting_step_id');
    }

    public function managementHierarchy(): BelongsTo
    {
        return $this->belongsTo(ManagementHierarchy::class, 'management_hierarchy_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
