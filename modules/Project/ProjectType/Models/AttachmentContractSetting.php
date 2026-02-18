<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttachmentContractSetting extends Model
{
    protected $table = "attachment_contract_settings";

    protected $fillable = [
        'project_type_id',
        'is_name',
        'is_type',
        'is_size',
        'is_creator',
        'is_create_date',
        'is_downloadable',
    ];

    protected $casts = [
        'is_name' => 'boolean',
        'is_type' => 'boolean',
        'is_size' => 'boolean',
        'is_creator' => 'boolean',
        'is_create_date' => 'boolean',
        'is_downloadable' => 'boolean',
    ];

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }
}
