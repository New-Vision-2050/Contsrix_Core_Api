<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use BasePackage\Shared\Traits\HasTranslations;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectTag extends Model
{
    use UuidTrait;
    use HasTranslations;

    public array $translatable = ['name'];

    protected $table = 'project_tags';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'code',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'name' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(ProjectManagement::class, 'project_tag_id', 'id');
    }
}
