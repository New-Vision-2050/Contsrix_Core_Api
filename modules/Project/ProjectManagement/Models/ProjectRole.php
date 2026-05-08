<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectRole extends Model
{
    use UuidTrait;

    protected $table = 'project_roles';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'project_id',
        'name',
        'slug',
        'description',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectManagement::class, 'project_id')->withoutGlobalScopes();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            ProjectPermission::class,
            'project_role_permissions',
            'project_role_id',
            'project_permission_id'
        )->withTimestamps();
    }

    public function projectEmployees(): HasMany
    {
        return $this->hasMany(ProjectEmployee::class, 'project_role_id');
    }
}
