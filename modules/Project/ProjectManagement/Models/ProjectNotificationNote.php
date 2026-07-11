<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use BasePackage\Shared\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

class ProjectNotificationNote extends Model
{
    use UuidTrait;
    use CustomBelongsToTenant;

    protected $table = 'project_notification_notes';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'project_notification_id',
        'user_id',
        'note',
    ];

    protected $casts = [
        'id' => 'string',
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
