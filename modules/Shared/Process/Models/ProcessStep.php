<?php

declare(strict_types=1);

namespace Modules\Shared\Process\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Shared\Process\Enums\ProcessStepStatus;
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

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<ProcessStep>  $query
     * @return \Illuminate\Database\Eloquent\Builder<ProcessStep>
     */
    public function scopePendingForAssignee($query, string $userId)
    {
        return $query->where('assigned_user_id', $userId)
            ->where('status', ProcessStepStatus::Pending);
    }
}
