<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Project\ProjectType\Models\ProjectType;

class ProjectNotificationSiteStatusType extends Model
{
    use UuidTrait;

    protected $table = 'project_notification_site_status_types';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'project_type_id',
        'name_ar',
        'name_en',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'project_type_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id')->withoutGlobalScopes();
    }

    public function keys(): HasMany
    {
        return $this->hasMany(ProjectNotificationSiteStatusTypeKey::class, 'site_status_type_id')
            ->orderBy('sort_order');
    }

    public function activeKeys(): HasMany
    {
        return $this->keys()->where('is_active', true);
    }
}
