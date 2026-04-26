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
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\User\Models\User;
use Modules\Shared\JobType\Models\JobType;
use Modules\JobTitle\Models\JobTitle;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;
use Nevadskiy\Tree\AsTree;
use Nevadskiy\Tree\Relations\HasManyDeep;
use OwenIt\Auditing\Contracts\Auditable;
use Stancl\Tenancy\Database\Concerns\BelongsToPrimaryModel;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

//use BasePackage\Shared\Traits\HasTranslations;

class ManagementHierarchy extends Model implements Auditable
{
    use HasFactory;
    use BaseFilterable;
    use AsTree;
    use CustomBelongsToTenant;
    use CalculateTreeManagementHierarchy;
    use HasRelationships; // Add the trait from staudenmeir/eloquent-has-many-deep
    use \OwenIt\Auditing\Auditable;


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
        "is_main",
        "users_count"
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'users_count' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
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

    public function clones()
    {
        return $this->hasMany(ManagementHierarchyDetail::class, 'reference_department_id', 'id');

    }


    public function users()//get all users under hierarchy not in company
    {
        return HasManyDeep::between($this , User::class,"management_hierarchy_id","id");
    }

    /**
     * Users professional data directly assigned to this branch (no recursion).
     */
    public function usersByBranch()
    {
        return $this->hasMany(UserProfessionalData::class, 'branch_id', 'id')
            ->where('company_id', $this->company_id);
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

    public function getUsersCountAttribute(): int
    {
        return (int) ($this->attributes['users_count'] ?? 0);
    }

    public function setUsersCountAttribute($value)
    {
        $this->attributes['users_count'] = $value;
    }

    public function incrementUsersCount()
    {
        $this->users_count++;
        $this->save();
    }

    public function decrementUsersCount()
    {
        $this->users_count--;
        $this->save();
    }
    public function attendanceConstraints()
    {
        return $this->morphToMany(
            AttendanceConstraint::class,
            'constrainable',
            'constrainables',
            'constrainable_id',
            'attendance_constraint_id'
        );
    }

   public function defaultAttendanceConstraint()
    {
        return $this->morphToMany(AttendanceConstraint::class, 'constrainable')
                    ->wherePivot('is_default', true);
    }

    public function usersCanAccess()
    {
        return $this->belongsToMany(User::class,"users_can_access_management_hierarchies","management_hierarchy_id","user_id");
    }
}
