<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Company\CompanyCore\Models\Company;
use Modules\User\Models\User;

class Branch extends Model
{
    use HasFactory;

    protected $table = 'branches';

    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'management_hierarchy_id',
        'name',
        'parent_id',
        'company_id',
        'path',
        'manager_id',
        'phone',
        'phone_code',
        'email',
        'latitude',
        'longitude',
        'is_first_branch',
        'is_main',
        'users_count',
    ];

    protected $casts = [
        'is_first_branch' => 'boolean',
        'is_main' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'users_count' => 'integer',
    ];

    /**
     * Get the management hierarchy that this branch belongs to.
     */
    public function managementHierarchy()
    {
        return $this->belongsTo(ManagementHierarchy::class, 'management_hierarchy_id');
    }

    /**
     * Get the company that this branch belongs to.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the manager of this branch.
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

}
