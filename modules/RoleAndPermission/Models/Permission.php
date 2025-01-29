<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Models;

use App\Casts\UuidCast;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\BaseFilterable;
use Spatie\Permission\Models\Permission as SpatiePermission;

// use BasePackage\Shared\Traits\HasTranslations;

class Permission extends SpatiePermission
{
    use UuidTrait;
    use BaseFilterable;
    use HasFactory;

    // use HasTranslations;
    // use SoftDeletes;

    public array $translatable = [];

    public $incrementing = false;
    protected $primaryKey = "id";

    protected $keyType = 'string';



    protected $casts = [
        'id' => UuidCast::class,
    ];
}
