<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\Process\Models\Process;
use Modules\User\Models\User;

class ProjectNotificationWorkStoppageReport extends Model
{
    use UuidTrait;
    use CustomBelongsToTenant;

    protected $table = 'project_notification_work_stoppage_reports';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'project_notification_id',
        'employee_task_request_id',
        'process_id',
        'procedure_setting_id',
        'other_notes',
        'status',
        'requested_by',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'id' => 'string',
        'reviewed_at' => 'datetime',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function projectNotification(): BelongsTo
    {
        return $this->belongsTo(ProjectNotification::class, 'project_notification_id')->withoutGlobalScopes();
    }

    public function employeeTask(): BelongsTo
    {
        return $this->belongsTo(EmployeeTaskRequest::class, 'employee_task_request_id')->withoutGlobalScopes();
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class, 'process_id')->withoutGlobalScopes();
    }

    public function reasons(): HasMany
    {
        return $this->hasMany(ProjectNotificationWorkStoppageReportReason::class, 'project_notification_work_stoppage_report_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by')->withoutGlobalScopes();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withoutGlobalScopes();
    }
}
