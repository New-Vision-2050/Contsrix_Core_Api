<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProjectTypeSchema extends Pivot
{
    protected $table = "project_type_schemas";

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'project_type_id',
        'schema_id',
    ];

    public function projectType()
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }

    public function schema()
    {
        return $this->belongsTo(Schema::class, 'schema_id');
    }
}
