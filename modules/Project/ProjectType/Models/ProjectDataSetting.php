<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDataSetting extends Model
{
    protected $table = "project_data_settings";

    protected $fillable = [
        'project_type_id',
        'is_reference_number',
        'is_name_project',
        'is_client',
        'is_responsible_engineer',
        'is_number_contract',
        'is_central_cost',
        'is_project_value',
        'is_start_date',
        'is_achievement_percentage',
    ];

    protected $casts = [
        'is_reference_number' => 'boolean',
        'is_name_project' => 'boolean',
        'is_client' => 'boolean',
        'is_responsible_engineer' => 'boolean',
        'is_number_contract' => 'boolean',
        'is_central_cost' => 'boolean',
        'is_project_value' => 'boolean',
        'is_start_date' => 'boolean',
        'is_achievement_percentage' => 'boolean',
    ];

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }
}
