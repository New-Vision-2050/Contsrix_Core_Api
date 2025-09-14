<?php

namespace Modules\Company\ManagementHierarchy\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\User\Models\User;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class UserCanAccessManagementHierarchy extends Model
{
    use UuidTrait;

    protected $table = 'users_can_access_management_hierarchies';

    protected $fillable = [
        'user_id',
        'management_hierarchy_id'
    ];

    protected $casts = [
        'user_id' => 'string',
        'management_hierarchy_id' => 'integer'
    ];

    /**
     * Get the user that has access to the management hierarchy
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the management hierarchy that the user can access
     */
    public function managementHierarchy(): BelongsTo
    {
        return $this->belongsTo(ManagementHierarchy::class, 'management_hierarchy_id');
    }

    /**
     * Get the branch that the user can access (alias for managementHierarchy)
     */
    public function branch(): BelongsTo
    {
        return $this->managementHierarchy();
    }
}
