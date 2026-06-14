<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Models;

use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use Modules\User\Models\User;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Models\Process;
use Modules\Project\ProjectManagement\Models\ProjectManagement;

class EmployeeTaskRequest extends Model
{
    use UuidTrait;
    use BaseFilterable;
    use CustomBelongsToTenant;

    protected $table = 'employee_task_requests';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'user_id',
        'serial_number',
        'title',
        'description',
        'project_id',
        'internal_process_type_id',
        'approval_responsible_id',
        'assignment_responsible_id',
        'duration_hours',
        'original_duration_hours',
        'task_date',
        'task_latitude',
        'task_longitude',
        'radius_meters',
        'procedure_setting_id',
        'current_procedure_step_id',
        'status',
        'time_from',
        'time_to',
        'total_task_hours',
        'total_pause_minutes',
        'shift_end_method',
        'start_location',
        'end_location',
        'timezone',
        'notes',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'last_extension_status',
        'location_confirmed_at',
    ];

    protected $casts = [
        'id'                      => 'string',
        'duration_hours'          => 'decimal:2',
        'original_duration_hours' => 'decimal:2',
        'total_task_hours'        => 'decimal:2',
        'task_latitude'           => 'decimal:7',
        'task_longitude'          => 'decimal:7',
        'start_location'          => 'array',
        'end_location'            => 'array',
        'task_date'               => 'date:Y-m-d',
        'time_from'               => 'datetime',
        'time_to'                 => 'datetime',
        'approved_at'             => 'datetime',
        'rejected_at'             => 'datetime',
        'cancelled_at'            => 'datetime',
        'location_confirmed_at'   => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectManagement::class, 'project_id')->withoutGlobalScopes();
    }

    public function internalProcessType(): BelongsTo
    {
        return $this->belongsTo(\Modules\Shared\InternalProcessType\Models\InternalProcessType::class, 'internal_process_type_id');
    }

    public function currentProcedureStep(): BelongsTo
    {
        return $this->belongsTo(ProcedureSettingStep::class, 'current_procedure_step_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(EmployeeTaskSession::class, 'employee_task_request_id');
    }

    public function extensionRequests(): HasMany
    {
        return $this->hasMany(EmployeeTaskExtensionRequest::class, 'employee_task_request_id');
    }

    public function approvalRequests(): HasMany
    {
        return $this->hasMany(EmployeeTaskApprovalRequest::class, 'employee_task_request_id');
    }

    public function isInStatus(EmployeeTaskStatus ...$statuses): bool
    {
        return in_array($this->status, array_map(fn (EmployeeTaskStatus $s) => $s->value, $statuses), true);
    }

    public function activeSession(): ?EmployeeTaskSession
    {
        return $this->sessions()->whereNull('end_time')->first();
    }

    public function hasPendingExtension(): bool
    {
        return $this->extensionRequests()
            ->where('status', 'pending')
            ->exists();
    }

    public function processes(): HasMany
    {
        return $this->hasMany(Process::class, 'processable_id');
    }

    public function employeeTaskProcess(): HasOne
    {
        return $this->hasOne(Process::class, 'processable_id')
            ->where('processable_type', 'employee_task_request');
    }

    public function hasPendingApprovalRequest(): bool
    {
        return $this->approvalRequests()
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * Callback when all processes for this EmployeeTaskRequest are completed.
     */
    public function onAllProcessesCompleted(Process $process): void
    {
        // If the task is still pending, mark it as approved.
        // This handles cases where the workflow completes without explicit approval.
        if ($this->status === EmployeeTaskStatus::Pending->value) {
            $this->update(['status' => EmployeeTaskStatus::Approved->value, 'approved_at' => now()]);
        }
    }

    /**
     * Callback when a process for this EmployeeTaskRequest fails.
     */
    public function onProcessFailed(Process $process): void
    {
        $this->update(['status' => EmployeeTaskStatus::Rejected->value, 'rejected_at' => now()]);
    }
}
