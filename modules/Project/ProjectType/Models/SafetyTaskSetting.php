<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafetyTaskSetting extends Model
{
    protected $table = 'safety_task_settings';

    protected $fillable = ['project_type_id', 'is_shown'];

    protected $casts = ['is_shown' => 'boolean'];

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }
}