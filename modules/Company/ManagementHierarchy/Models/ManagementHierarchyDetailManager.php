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

class ManagementHierarchyDetailManager extends Model
{
    use HasFactory;

    use UuidTrait;
    use BaseFilterable;

//    use AsTree;

    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];
    protected $primaryKey = 'id';

    protected $table = "management_hierarchy_deputy_managers";


    public $incrementing = false;

//    protected $keyType = 'string';

    protected $fillable = [
        "id",
        "deputy_manager_id",
        "management_hierarchy_detail_id"
    ];

    protected $casts = [
        "id" => 'string',
        'deputy_manager_id' => 'string',
    ];




    protected static function newFactory(): ManagementHierarchyFactory
    {
        return ManagementHierarchyFactory::new();
    }

    public function managementHierarchyDetail()
    {
        return $this->belongsTo(ManagementHierarchyDetail::class, "management_hierarchy_detail_id");
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'deputy_manager_id', 'id');
    }


}
