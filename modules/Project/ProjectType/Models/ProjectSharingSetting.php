<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSharingSetting extends Model
{
    protected $table = "project_sharing_settings";

    protected $fillable = [
        'project_type_id',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'int',
    ];

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }
}
