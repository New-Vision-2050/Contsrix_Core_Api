<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Models;

use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\SubscriptionSystem\Feature\Models\Feature;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Stancl\Tenancy\Database\Concerns\BelongsToPrimaryModel;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Permission extends SpatiePermission
{
    use UuidTrait;
    use BaseFilterable;
    use HasFactory;
    use BelongsToTenant;

    public array $translatable = [];

    public $incrementing = false;
    protected $primaryKey = 'id';
    protected $keyType = 'string';

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

    protected $fillable = [
        'name',
        'guard_name',
        'resource',
        'action',
        'program_id',
        'sub_entity_id',
        'company_id',
        'status'
    ];

    /**
     * Relation to Program model (nullable).
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(\Modules\Program\Models\Program::class, 'program_id');
    }

    /**
     * Relation to SubEntity model (nullable).
     */
    public function subEntity(): BelongsTo
    {
        return $this->belongsTo(\Modules\SubEntity\Models\SubEntity::class, 'sub_entity_id');
    }
}
