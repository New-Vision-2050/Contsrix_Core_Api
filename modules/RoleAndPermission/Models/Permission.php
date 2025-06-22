<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Models;

use App\Casts\CompanyRoleCast;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\BaseFilterable;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Stancl\Tenancy\Database\Concerns\BelongsToPrimaryModel;

// use BasePackage\Shared\Traits\HasTranslations;

class Permission extends SpatiePermission
{
    use UuidTrait;
    use BaseFilterable;
    use HasFactory;
    use BelongsToPrimaryModel;

    // use HasTranslations;
    // use SoftDeletes;

    public array $translatable = [];

    public $incrementing = false;
    protected $primaryKey = "id";

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'guard_name',
        'company_id'
    ];

    public function getRelationshipToPrimaryModel(): string
    {
        return "roles";
    }

    /**
     * Get the company that owns the permission.
     */
    public function company()
    {
        return $this->belongsTo('Modules\Company\CompanyCore\Models\Company', 'company_id');
    }
}
