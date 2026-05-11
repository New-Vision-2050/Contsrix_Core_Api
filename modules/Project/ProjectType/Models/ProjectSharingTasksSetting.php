<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSharingTasksSetting extends Model
{
    protected $table = 'project_sharing_tasks_setting';

    protected $fillable = [
        'project_type_id',
        'project_sharing_work_order_id',
        'project_sharing_task_id',
    ];

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(ProjectSharingWorkOrder::class, 'project_sharing_work_order_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectSharingTask::class, 'project_sharing_task_id');
    }
}
