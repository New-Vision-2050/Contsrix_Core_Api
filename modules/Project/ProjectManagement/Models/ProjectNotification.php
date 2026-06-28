<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\User\Models\User;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProjectNotification extends Model implements HasMedia
{
    use UuidTrait;
    use BaseFilterable;
    use CustomBelongsToTenant;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $table = 'project_notifications';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'project_id',
        'employee_task_request_id',
        'notification_number',
        'notification_type',
        'severity',
        'work_type',
        'feeder_number',
        'work_description',
        'contractor_id',
        'contractor_name',
        'contractor_number',
        'contractor_technical_number',
        'contractor_technical_name',
        'contractor_category',
        'contractor_notes',
        'contractor_mobile',
        'task_latitude',
        'task_longitude',
        'location_radius',
        'location_link',
        'repair_point',
        'assigned_user_id',
        'selected_distance_meters',
        'status',
        'created_by_user_id',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'task_date',
        'task_time',
        'duration_hours',
        'notes',
    ];

    protected $casts = [
        'id' => 'string',
        'task_latitude' => 'decimal:7',
        'task_longitude' => 'decimal:7',
        'location_radius' => 'integer',
        'selected_distance_meters' => 'integer',
        'duration_hours' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'task_date' => 'date:Y-m-d',
        'task_time' => 'datetime:H:i',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectManagement::class, 'project_id')->withoutGlobalScopes();
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class, 'contractor_id')->withoutGlobalScopes();
    }

    public function employeeTask(): BelongsTo
    {
        return $this->belongsTo(EmployeeTaskRequest::class, 'employee_task_request_id')->withoutGlobalScopes();
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id')->withoutGlobalScopes();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id')->withoutGlobalScopes();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by')->withoutGlobalScopes();
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by')->withoutGlobalScopes();
    }
}
