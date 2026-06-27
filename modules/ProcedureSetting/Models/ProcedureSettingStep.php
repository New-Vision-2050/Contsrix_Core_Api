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
        'skipping_period',
        'notify_by_email',
        'notify_by_whatsapp',
        'notify_by_sms',
        'notify_by_push',
        'escalation_management_hierarchy_id',
        'step_order',
        'action_taker_type',
        'action_taker_management_hierarchy_type',
        'action_taker_alternative_management_hierarchy_type',
        'action_taker_specific_procedure_type',
        'action_taker_specific_procedure_id',
        'action_taker_management_hierarchies',
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
        'skipping_period'                 => 'integer',
        'notify_by_email'                 => 'boolean',
        'notify_by_whatsapp'              => 'boolean',
        'notify_by_sms'                   => 'boolean',
        'notify_by_push'                  => 'boolean',
        'escalation_management_hierarchy_id' => 'integer',
        'step_order'                         => 'integer',
        'action_taker_type'                              => \Modules\ProcedureSetting\Enums\ActionTakerType::class,
        'action_taker_management_hierarchy_type'         => \Modules\ProcedureSetting\Enums\ActionTakerManagementHierarchyType::class,

        // Array of ActionTakerManagementHierarchyType values (JSON-encoded in DB).
        // e.g. ["branch_manager", "deputy_manager"]
        'action_taker_alternative_management_hierarchy_type' => 'array',

        // Array of specific-procedure type strings (JSON-encoded in DB).
        // e.g. ["branch", "management"]
        'action_taker_specific_procedure_type' => 'array',

        // Array of specific-procedure IDs (JSON-encoded in DB) parallel to the types array.
        // e.g. ["5", "12"]
        'action_taker_specific_procedure_id'   => 'array',

        // Array of {action_taker_management_hierarchy_type, is_Deputy_Director} objects.
        'action_taker_management_hierarchies'  => 'array',
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
