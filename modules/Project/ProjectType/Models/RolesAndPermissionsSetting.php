<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\RoleAndPermission\Models\Permission;

class RolesAndPermissionsSetting extends Model
{
    protected $table = "roles_and_permissions_settings";

    protected $fillable = [
        'project_type_id',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'int',
    ];

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }


}
