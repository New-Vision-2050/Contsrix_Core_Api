<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use Modules\User\Models\User;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class EmployeeTaskApprovalRequest extends Model implements HasMedia
{
    use UuidTrait;
    use InteractsWithMedia;

    protected $table = 'employee_task_approval_requests';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'employee_task_request_id',
        'company_id',
        'requested_by',
        'notes',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'current_procedure_step_id',
    ];

    protected $casts = [
        'id'          => 'string',
        'reviewed_at' => 'datetime',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(EmployeeTaskRequest::class, 'employee_task_request_id');
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function currentProcedureStep(): BelongsTo
    {
        return $this->belongsTo(ProcedureSettingStep::class, 'current_procedure_step_id');
    }
}
