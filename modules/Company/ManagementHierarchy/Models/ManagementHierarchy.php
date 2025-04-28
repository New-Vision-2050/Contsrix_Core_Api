<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Models;

use App\Traits\CustomBelongsToTenant;
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

class ManagementHierarchy extends Model
{
    use HasFactory;

//    use UuidTrait;
    use BaseFilterable;

    use AsTree;
    use CustomBelongsToTenant;

    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];
    protected $primaryKey = 'id';

    protected $table = "management_hierarchies";

    protected $with = ["user"];//,"users"

    public $incrementing = false;


    protected $fillable = [
        "id",
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


//    public function users()//get all users under hierarchy not in company
//    {
//        return HasManyDeep::between($this , User::class,"management_hierarchy_id","id");
//    }

    public function detail()
    {
        return $this->hasOne(ManagementHierarchyDetail::class, 'management_hierarchy_id');
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
}
