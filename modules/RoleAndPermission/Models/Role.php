<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Models;

use App\Casts\UuidCast;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\BaseFilterable;
use Spatie\Permission\Models\Role as SpatieRole;
// use BasePackage\Shared\Traits\HasTranslations;

class Role extends SpatieRole
{
    use UuidTrait;
    use BaseFilterable;
    use HasFactory;
//    use HasUuids;

    // use HasTranslations;
    // use SoftDeletes;

    public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';
    protected $primaryKey = 'id';



}
