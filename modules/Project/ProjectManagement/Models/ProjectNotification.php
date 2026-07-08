<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Modules\Company\CompanyCore\Models\Company;
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
        'machine_number',
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
        'permit_source',
        'permit_recipient',
        'assigned_user_ids',
        'all_users_can_approve',
        'independent_progress',
        'selected_distance_meters',
        'status',
        'created_by_user_id',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'confirmation_receive_date',
        'location_confirmed_at',
        'last_site_status_reminder_sent_at',
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
        'confirmation_receive_date' => 'datetime',
        'location_confirmed_at' => 'datetime',
        'last_site_status_reminder_sent_at' => 'datetime',
        'task_date' => 'date:Y-m-d',
        'task_time' => 'datetime:H:i',
        'assigned_user_ids' => 'array',
        'all_users_can_approve' => 'boolean',
        'independent_progress' => 'boolean',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
        $this->addMediaCollection('site_status_update_attachments');
        $this->addMediaCollection('fine_attachments');
        $this->addMediaCollection('work_stoppage_report_attachments');
        $this->addMediaCollection('work_resumption_attachments');
        $this->addMediaCollection('update_attachments');
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

    /**
     * Preloaded collection of assigned User models (set by repository for eager loading).
     */
    protected ?Collection $preloadedAssignedUsers = null;

    public function setPreloadedAssignedUsers(Collection $users): void
    {
        $this->preloadedAssignedUsers = $users;
    }

    /**
     * All users assigned to this notification (from the assigned_user_ids JSON array).
     */
    public function getAssignedUsersAttribute(): Collection
    {
        if ($this->preloadedAssignedUsers !== null) {
            return $this->preloadedAssignedUsers;
        }

        $ids = $this->assigned_user_ids ?? [];
        if (empty($ids)) {
            return collect();
        }

        return User::withoutGlobalScopes()->whereIn('id', $ids)->get();
    }

    /**
     * Convenience accessor: the first assigned user (for backward compatibility).
     */
    public function getAssignedUserAttribute(): ?User
    {
        return $this->assigned_users->first();
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->withoutGlobalScopes();
    }

    public function siteStatusUpdates(): HasMany
    {
        return $this->hasMany(ProjectNotificationSiteStatusUpdate::class, 'project_notification_id');
    }
}
