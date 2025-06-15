<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Models\CompanyAddress;
use Modules\Company\ManagementHierarchy\Database\factories\ManagementHierarchyFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\User\Models\User;
use Nevadskiy\Tree\AsTree;
use Nevadskiy\Tree\Relations\HasManyDeep;
use Stancl\Tenancy\Database\Concerns\BelongsToPrimaryModel;

//use BasePackage\Shared\Traits\HasTranslations;

class ManagementHierarchyDetail extends Model
{
    use HasFactory;


    use BaseFilterable;

//    use AsTree;
    use BelongsToPrimaryModel;

    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];
    protected $primaryKey = 'id';

    protected $table = "management_hierarchy_details";


    protected $fillable = [
        "id",
        "description",
        "reference_user_id",
        "management_hierarchy_id",
        "branch_id"
    ];

    protected $casts = [
        'reference_user_id' => 'string',
    ];


    //example for nested set

//    public function users()
//    {
//        return HasManyDeep::between($this , User::class,"management_hierarchy_id","id");
//    }


    protected static function newFactory(): ManagementHierarchyFactory
    {
        return ManagementHierarchyFactory::new();
    }

    /**
     * Get the management hierarchy this detail belongs to
     */
    public function managementHierarchy()
    {
        return $this->belongsTo(ManagementHierarchy::class, "management_hierarchy_id");
    }

    /**
     * Get the deputy manager relationships for this management hierarchy detail
     */
    public function deputyManagerRelations()
    {
        return $this->hasMany(ManagementHierarchyDetailManager::class, 'management_hierarchy_detail_id');
    }

    /**
     * Get the deputy managers for this management hierarchy detail
     */
    public function deputyManagers()
    {
        return $this->hasManyThrough(
            User::class,
            ManagementHierarchyDetailManager::class,
            'management_hierarchy_detail_id',
            'id',
            'id',
            'deputy_manager_id'
        );
    }

    public function referanceUser()
    {
        return $this->belongsTo(User::class , "reference_user_id");
    }

    public function getRelationshipToPrimaryModel(): string
    {
        return "managementHierarchy";
    }
}
