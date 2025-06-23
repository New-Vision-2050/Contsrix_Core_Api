<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Models;

use App\Casts\CompanyRoleCast;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Subscription\Models\Feature;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Stancl\Tenancy\Database\Concerns\BelongsToPrimaryModel;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

// use BasePackage\Shared\Traits\HasTranslations;

class Permission extends SpatiePermission
{
    use UuidTrait;
    use BaseFilterable;
    use HasFactory;
    use BelongsToTenant;

    // use HasTranslations;
    // use SoftDeletes;

    public array $translatable = [];

    public $incrementing = false;
    protected $primaryKey = "id";

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'guard_name',
        'company_id',
        'status'
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
    
    /**
     * Get the features associated with this permission
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'feature_permission', 'permission_id', 'feature_id')
            ->withTimestamps();
    }
}
