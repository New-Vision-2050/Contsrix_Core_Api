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

class ProjectNotificationLocationConfirmation extends Model
{
    use UuidTrait;
    use CustomBelongsToTenant;

    protected $table = 'project_notification_location_confirmations';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'project_notification_id',
        'employee_task_request_id',
        'process_id',
        'procedure_setting_id',
        'latitude',
        'longitude',
        'distance_meters',
        'is_inside_location',
        'status',
        'requested_by',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'id' => 'string',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'distance_meters' => 'decimal:2',
        'is_inside_location' => 'boolean',
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

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by')->withoutGlobalScopes();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withoutGlobalScopes();
    }
}
