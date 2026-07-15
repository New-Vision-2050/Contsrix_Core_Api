<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectNotificationSiteStatusTypeKey extends Model
{
    use UuidTrait;

    protected $table = 'project_notification_site_status_type_keys';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'site_status_type_id',
        'name_ar',
        'name_en',
        'key',
        'field_type',
        'options',
        'show_in_site_status_updates',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'options' => 'array',
        'show_in_site_status_updates' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function siteStatusType(): BelongsTo
    {
        return $this->belongsTo(ProjectNotificationSiteStatusType::class, 'site_status_type_id');
    }

    public function scopeVisibleInSiteStatusUpdates($query)
    {
        return $query->where('show_in_site_status_updates', true);
    }
}
