<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectNotificationSiteStatusValue extends Model
{
    use UuidTrait;

    protected $table = 'project_notification_site_status_values';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'project_notification_id',
        'site_status_type_key_id',
        'value',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function projectNotification(): BelongsTo
    {
        return $this->belongsTo(ProjectNotification::class, 'project_notification_id')->withoutGlobalScopes();
    }

    public function key(): BelongsTo
    {
        return $this->belongsTo(ProjectNotificationSiteStatusTypeKey::class, 'site_status_type_key_id');
    }
}
