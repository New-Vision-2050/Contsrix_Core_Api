<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportForm extends Model
{
    protected $table = 'report_forms';

    protected $fillable = [
        'project_type_id',
        'project_sharing_work_order_id',
        'name',
        'question',
        'value',
        'number_of_attachments',
        'notes',
    ];

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(ProjectSharingWorkOrder::class, 'project_sharing_work_order_id');
    }
}
