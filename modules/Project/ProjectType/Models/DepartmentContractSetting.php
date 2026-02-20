<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentContractSetting extends Model
{
    protected $table = "department_contract_setting";

    protected $fillable = [
        'project_type_id',
        'is_all_data_visible',
    ];

    protected $casts = [
        'is_all_data_visible' => 'int',
    ];

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }
}
