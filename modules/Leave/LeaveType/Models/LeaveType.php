<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveType\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Leave\LeaveType\Database\factories\LeaveTypeFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;

//use BasePackage\Shared\Traits\HasTranslations;

class LeaveType extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;
    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'is_payed',
        'is_deduct_from_balance',
        'conditions',
        "company_id",
    ];

    protected $casts = [
        'id' => 'string',
//        'is_payed' => 'boolean',
//        'is_deduct_from_balance' => 'boolean',
    ];

    protected static function newFactory(): LeaveTypeFactory
    {
        return LeaveTypeFactory::new();
    }

    /**
     * Get the branches (management hierarchies with type = 'branch') associated with this leave type
     */
    public function branches()
    {
        return $this->belongsToMany(
            ManagementHierarchy::class,
            'leave_type_management_hierarchy',
            'leave_type_id',
            'management_hierarchy_id'
        )->where('type', 'branch');
    }

    /**
     * Get all management hierarchies associated with this leave type (not filtered by type)
     */
    public function managementHierarchies()
    {
        return $this->belongsToMany(
            ManagementHierarchy::class,
            'leave_type_management_hierarchy',
            'leave_type_id',
            'management_hierarchy_id'
        );
    }
}
