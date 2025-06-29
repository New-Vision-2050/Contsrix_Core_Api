<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Company\CompanyCore\Models\Company;
use Modules\User\Models\User;

class Management extends Model
{
    use HasFactory;

    protected $table = 'managements';

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
        'is_main',
    ];

    protected $casts = [
        'is_main' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    /**
     * Get the management hierarchy that this management unit belongs to.
     */
    public function managementHierarchy()
    {
        return $this->belongsTo(ManagementHierarchy::class, 'management_hierarchy_id');
    }

    /**
     * Get the company that this management unit belongs to.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the manager of this management unit.
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    // If you plan to use the Nevadskiy/laravel-tree package for managements as well,
    // you might want to add 'use AsTree;' and configure it here.
    // For now, it's a flat representation linked to ManagementHierarchy.
}
