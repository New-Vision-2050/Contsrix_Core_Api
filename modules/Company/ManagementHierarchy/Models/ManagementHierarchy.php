<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Models;

use App\Traits\CalculateTreeManagementHierarchy;
use App\Traits\CustomBelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Models\CompanyAddress;
use Modules\Company\ManagementHierarchy\Database\factories\ManagementHierarchyFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\User\Models\User;
use Nevadskiy\Tree\AsTree;
use Nevadskiy\Tree\Relations\HasManyDeep;
use Stancl\Tenancy\Database\Concerns\BelongsToPrimaryModel;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

//use BasePackage\Shared\Traits\HasTranslations;

class ManagementHierarchy extends Model
{
    use HasFactory;
    use BaseFilterable;
    use AsTree;
    use CustomBelongsToTenant;
    use CalculateTreeManagementHierarchy;
    use HasRelationships; // Add the trait from staudenmeir/eloquent-has-many-deep

    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    protected $table = "management_hierarchies";

    protected $with = ["user"];

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'parent_id',
        'company_id',
        'path',
        "type",
        "manager_id",
        "phone",
        "phone_code",
        "email",
        "latitude",
        "longitude",
        "is_first_branch",
        "is_main"
    ];



    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, "manager_id", "id");
    }

    public function directUserChildren()
    {
        return $this->hasMany(User::class,"management_hierarchy_id","id");
    }


    public function users()//get all users under hierarchy not in company
    {
        return HasManyDeep::between($this , User::class,"management_hierarchy_id","id");
    }

    public function detail()
    {
        return $this->hasOne(ManagementHierarchyDetail::class, 'management_hierarchy_id');
    }

    /**
     * Direct access to deputy managers using eloquent-has-many-deep
     * This eliminates the N+1 query problem by providing a direct relation
     */
    public function deputyManagers()
    {
        return $this->hasManyDeep(
            User::class,
            [ManagementHierarchyDetail::class, ManagementHierarchyDetailManager::class],
            [
                'management_hierarchy_id', // Foreign key on ManagementHierarchyDetail table
                'management_hierarchy_detail_id', // Foreign key on ManagementHierarchyDetailManager table
                'id', // Foreign key on User table
            ],
            [
                'id', // Local key on ManagementHierarchy model
                'id', // Local key on ManagementHierarchyDetail model
                'deputy_manager_id', // Local key on ManagementHierarchyDetailManager model
            ]
        ); // Ensure no duplicate users are returned
    }



    protected static function newFactory(): ManagementHierarchyFactory
    {
        return ManagementHierarchyFactory::new();
    }


    public function address()
    {
        return $this->hasOne(CompanyAddress::class, 'management_hierarchy_id');
    }

    public function getRelationshipToPrimaryModel(): string
    {
        return "company";
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        // Clear cache for the node and all ancestors when a node is saved or deleted
        static::saved(function ($node) {
            static::clearRelatedCaches($node);
        });

        static::deleted(function ($node) {
            static::clearRelatedCaches($node);
        });
    }

    /**
     * Clear caches related to this node and its ancestors
     */
    protected static function clearRelatedCaches($node)
    {
        // Clear cache for the current node
        Cache::forget($node->getHierarchyCountsCacheKey());

        // Clear cache for all ancestor nodes as their counts are affected
        $ancestors = $node->ancestors()->get();
        foreach ($ancestors as $ancestor) {
            Cache::forget($ancestor->getHierarchyCountsCacheKey());
        }
    }

    /**
     * Get the cache key for hierarchy counts
     *
     * @return string
     */
    public function getHierarchyCountsCacheKey(): string
    {
        return "management_hierarchy_{$this->id}_counts";
    }

    /**
     * Get merged users from different related collections
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllUsersAttribute()
    {
        //get manager if found
        $manager = $this->user;
        if($this->user)
        {
            $manager->type = "manager";
        }
        $manager = $this->user ? collect([$manager]) : collect([]);

        // Get deputy managers and mark them with type
        $deputyManagers = $this->deputyManagers ?? collect([]);
        $deputyManagers->map(function ($user){
           $user->type = "deputy_manager";
        });

        //get direct user Children
        $childrenUsers = $this->directUserChildren ?? collect([]);
        $childrenUsers->map(function ($user){
           $user->type = "user";
        });

        //merging are put unique id
        return $manager->merge($deputyManagers)->merge($childrenUsers)->unique('id');
    }
}
