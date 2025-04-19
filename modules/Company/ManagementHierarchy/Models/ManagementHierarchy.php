<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Company\ManagementHierarchy\Database\factories\ManagementHierarchyFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Nevadskiy\Tree\AsTree;

//use BasePackage\Shared\Traits\HasTranslations;

class ManagementHierarchy extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use AsTree;
    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): ManagementHierarchyFactory
    {
        return ManagementHierarchyFactory::new();
    }
}
