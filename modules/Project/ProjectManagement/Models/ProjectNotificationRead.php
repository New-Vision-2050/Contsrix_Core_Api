<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

class ProjectNotificationRead extends Model
{
    use UuidTrait;

    protected $table = 'project_notification_reads';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'project_notification_id',
        'user_id',
        'read_at',
    ];

    protected $casts = [
        'id' => 'string',
        'read_at' => 'datetime',
    ];

    public function projectNotification(): BelongsTo
    {
        return $this->belongsTo(ProjectNotification::class, 'project_notification_id')->withoutGlobalScopes();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withoutGlobalScopes();
    }
}
