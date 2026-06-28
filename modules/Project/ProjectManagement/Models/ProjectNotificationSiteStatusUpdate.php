<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\Process\Models\Process;
use Modules\User\Models\User;

class ProjectNotificationSiteStatusUpdate extends Model
{
    use UuidTrait;
    use CustomBelongsToTenant;

    protected $table = 'project_notification_site_status_updates';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'project_notification_id',
        'employee_task_request_id',
        'process_id',
        'procedure_setting_id',
        'update_date',
        'update_time',
        'site_status_id',
        'current_site_status_id',
        'work_stages_completed',
        'current_status_description',
        'completion_percentage',
        'updates_obstacles',
        'additional_notes',
        'status',
        'requested_by',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'id' => 'string',
        'update_date' => 'date:Y-m-d',
        'completion_percentage' => 'decimal:2',
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

    public function siteStatus(): BelongsTo
    {
        return $this->belongsTo(ProjectNotificationSiteStatus::class, 'site_status_id')->withoutGlobalScopes();
    }

    public function currentSiteStatus(): BelongsTo
    {
        return $this->belongsTo(ProjectNotificationSiteStatus::class, 'current_site_status_id')->withoutGlobalScopes();
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
