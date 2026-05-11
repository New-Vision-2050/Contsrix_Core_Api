<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPermitTask extends Model
{
    protected $table = 'order_permit_tasks';

    protected $fillable = [
        'project_type_id',
        'code',
        'name',
    ];

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }
}
