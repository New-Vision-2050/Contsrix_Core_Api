<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Company\Models\Company;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\Process\Models\Process;

class ProjectNotificationTaskPostponement extends Model
{
    use HasUuids;
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'project_notification_task_postponements';

    protected $fillable = [
        'company_id',
        'project_notification_id',
        'employee_task_request_id',
        'process_id',
        'procedure_setting_id',
        'previous_task_date',
        'previous_task_time',
        'new_task_date',
        'new_task_time',
        'reason',
        'status',
        'requested_by',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'project_notification_id' => 'string',
        'employee_task_request_id' => 'string',
        'process_id' => 'string',
        'procedure_setting_id' => 'string',
        'previous_task_date' => 'date:Y-m-d',
        'previous_task_time' => 'datetime:H:i',
        'new_task_date' => 'date:Y-m-d',
        'new_task_time' => 'datetime:H:i',
        'reviewed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function projectNotification(): BelongsTo
    {
        return $this->belongsTo(ProjectNotification::class, 'project_notification_id');
    }

    public function employeeTask(): BelongsTo
    {
        return $this->belongsTo(EmployeeTaskRequest::class, 'employee_task_request_id');
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class, 'process_id');
    }
}
