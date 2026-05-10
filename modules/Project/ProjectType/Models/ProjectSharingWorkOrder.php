<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectSharingWorkOrder extends Model
{
    protected $table = 'project_sharing_work_orders';

    protected $fillable = [
        'project_type_id',
        'code',
        'description',
        'type',
    ];

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }

    public function reportForms(): HasMany
    {
        return $this->hasMany(ReportForm::class, 'project_sharing_work_order_id');
    }
}
