<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectPhaseStatus extends Model
{
    protected $table = 'project_phase_statuses';

    protected $fillable = [
        'project_completion_phase_id',
        'name',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProjectCompletionPhase::class, 'project_completion_phase_id');
    }
}
