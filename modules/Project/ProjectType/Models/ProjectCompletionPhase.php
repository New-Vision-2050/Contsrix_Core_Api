<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectCompletionPhase extends Model
{
    protected $table = 'project_completion_phases';

    protected $fillable = [
        'order_permit_department_id',
        'name',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(OrderPermitDepartment::class, 'order_permit_department_id');
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(ProjectPhaseStatus::class, 'project_completion_phase_id');
    }
}
