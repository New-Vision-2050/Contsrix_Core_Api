<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use BasePackage\Shared\Traits\BaseFilterable;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;

class ProcedureSettingStep extends Model
{
    use HasFactory;
    use BaseFilterable;
    use BelongsToTenant;

    protected $table = 'procedure_setting_steps';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'is_accept',
        'is_approve',
        'forms',
        'procedure_setting_id',
        'company_id',
        'name',
        'branch_id',
        'management_id',
        'is_view_only',
        'is_return_with_notes',
        'requires_approval_within_period',
        'approval_within_days',
        'approval_within_hours',
        'auto_approval_within_hours',
        'notify_by_email',
        'notify_by_whatsapp',
        'notify_by_sms',
        'escalation_management_hierarchy_id',
        'step_order',
    ];

    protected $casts = [
        'is_accept'                       => 'boolean',
        'is_approve'                      => 'boolean',
        'branch_id'                       => 'integer',
        'management_id'                   => 'integer',
        'is_view_only'                    => 'boolean',
        'is_return_with_notes'            => 'boolean',
        'requires_approval_within_period' => 'boolean',
        'approval_within_days'            => 'integer',
        'approval_within_hours'           => 'integer',
        'auto_approval_within_hours'      => 'integer',
        'notify_by_email'                 => 'boolean',
        'notify_by_whatsapp'              => 'boolean',
        'notify_by_sms'                   => 'boolean',
        'escalation_management_hierarchy_id' => 'integer',
        'step_order'                         => 'integer',
    ];

    public function getRelationshipToPrimaryModel(): string
    {
        return 'company';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function procedureSetting(): BelongsTo
    {
        return $this->belongsTo(ProcedureSetting::class, 'procedure_setting_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(ManagementHierarchy::class, 'branch_id');
    }

    public function management(): BelongsTo
    {
        return $this->belongsTo(ManagementHierarchy::class, 'management_id');
    }

    public function escalationManagementHierarchy(): BelongsTo
    {
        return $this->belongsTo(ManagementHierarchy::class, 'escalation_management_hierarchy_id');
    }

    public function actionTakers(): HasMany
    {
        return $this->hasMany(ProcedureSettingStepActionTaker::class, 'procedure_setting_step_id');
    }

    public function concernedManagementHierarchies(): HasMany
    {
        return $this->hasMany(ProcedureSettingStepConcernedManagementHierarchy::class, 'procedure_setting_step_id');
    }
}
