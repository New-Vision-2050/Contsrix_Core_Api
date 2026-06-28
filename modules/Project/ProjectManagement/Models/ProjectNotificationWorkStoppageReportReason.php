<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectNotificationWorkStoppageReportReason extends Model
{
    use UuidTrait;

    protected $table = 'project_notification_work_stoppage_report_reasons';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'project_notification_work_stoppage_report_id',
        'work_stoppage_reason_id',
        'reason_name_ar',
        'reason_name_en',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'id' => 'string',
        'sort_order' => 'integer',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(ProjectNotificationWorkStoppageReport::class, 'project_notification_work_stoppage_report_id')->withoutGlobalScopes();
    }

    public function reason(): BelongsTo
    {
        return $this->belongsTo(ProjectNotificationWorkStoppageReason::class, 'work_stoppage_reason_id')->withoutGlobalScopes();
    }
}
