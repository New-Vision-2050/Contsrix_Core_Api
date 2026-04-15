<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProjectPermission extends Model
{
    use UuidTrait;
    use HasTranslations;

    protected $table = 'project_permissions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected array $translatable = ['title'];

    protected $fillable = [
        'name',
        'submodule',
        'action',
        'title',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            ProjectRole::class,
            'project_role_permissions',
            'project_permission_id',
            'project_role_id'
        )->withTimestamps();
    }
}
