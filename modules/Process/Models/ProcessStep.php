<?php

declare(strict_types=1);

namespace Modules\Process\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Process\Enums\ProcessStepStatus;
use Modules\User\Models\User;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;

class ProcessStep extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'process_steps';

    protected $fillable = [
        'process_id',
        'step_id',
        'template_step_order',
        'assigned_user_id',
        'escalation_management_hierarchy_id',
        'status',
        'action_by',
        'notify_by_sms',
        'auto_approval_within_hours',
        'is_view_only',
        'is_return_with_notes',
        'approval_within_days',
        'approval_within_hours',
        'notify_by_email',
        'notify_by_whatsapp',
        'acted_at',
    ];

    protected $casts = [
        'id'                   => 'string',
        'process_id'           => 'string',
        'step_id'              => 'integer',
        'template_step_order'  => 'integer',
        'assigned_user_id'                        => 'string',
        'escalation_management_hierarchy_id' => 'integer',
        'status'                             => ProcessStepStatus::class,
        'action_by'            => 'string',
        'acted_at'             => 'datetime',
    ];

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class, 'process_id');
    }

    public function procedureSettingStep(): BelongsTo
    {
        return $this->belongsTo(ProcedureSettingStep::class, 'step_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function escalationManagementHierarchy(): BelongsTo
    {
        return $this->belongsTo(ManagementHierarchy::class, 'escalation_management_hierarchy_id');
    }

    public function actionByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_by');
    }

    public function actionTakers(): HasMany
    {
        return $this->hasMany(ProcessStepActionTaker::class, 'process_step_id');
    }
    public function scopePendingForAssignee($query, string $userId)
    {
        return $query->where('assigned_user_id', $userId)
            ->where('status', ProcessStepStatus::Pending);
    }
}
