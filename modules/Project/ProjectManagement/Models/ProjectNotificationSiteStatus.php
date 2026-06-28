<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;

class ProjectNotificationSiteStatus extends Model
{
    use UuidTrait;

    protected $table = 'project_notification_site_statuses';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name_ar',
        'name_en',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
}
