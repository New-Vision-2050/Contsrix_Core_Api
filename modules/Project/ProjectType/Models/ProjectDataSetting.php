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
        'is_reference_number' => 'int',
        'is_name_project' => 'int',
        'is_client' => 'int',
        'is_responsible_engineer' => 'int',
        'is_number_contract' => 'int',
        'is_central_cost' => 'int',
        'is_project_value' => 'int',
        'is_start_date' => 'int',
        'is_achievement_percentage' => 'int',
    ];

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }
}
