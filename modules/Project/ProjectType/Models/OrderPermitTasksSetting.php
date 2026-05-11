<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPermitTasksSetting extends Model
{
    protected $table = 'order_permit_tasks_setting';

    protected $fillable = [
        'project_type_id',
        'order_permit_id',
        'order_permit_task_id',
    ];

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }

    public function orderPermit(): BelongsTo
    {
        return $this->belongsTo(OrderPermit::class, 'order_permit_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(OrderPermitTask::class, 'order_permit_task_id');
    }
}
