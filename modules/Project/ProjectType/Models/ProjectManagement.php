<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Project\ProjectManagement\Models\ProjectManagement as ProjectModel;

class ProjectManagement extends Model
{
    protected $table = 'project_managements';

    protected $fillable = [
        'project_id',
        'name',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectModel::class, 'project_id');
    }

    public function projectOrderPermits(): HasMany
    {
        return $this->hasMany(ProjectOrderPermit::class, 'project_management_id');
    }
}
