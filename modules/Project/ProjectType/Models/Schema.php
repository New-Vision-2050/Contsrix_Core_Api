<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use BasePackage\Shared\Traits\BaseFilterable;

class Schema extends Model
{
    use HasFactory;
    use BaseFilterable;

    protected $table = "project_schemas";

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'name',
    ];

    public function projectTypes()
    {
        return $this->belongsToMany(
            ProjectType::class,
            'project_type_schemas',
            'schema_id',
            'project_type_id'
        );
    }
}
