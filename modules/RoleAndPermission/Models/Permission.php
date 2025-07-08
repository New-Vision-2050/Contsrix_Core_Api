<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Models;

use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Subscription\Models\Feature;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Stancl\Tenancy\Database\Concerns\BelongsToPrimaryModel;

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

    // Allow mass assignment for new columns
    protected $fillable = [
        'name',
        'guard_name',
        'resource',
        'action',
        'program_id',
        'sub_entity_id',
    ];

    /**
     * Get the company that owns the permission.
     */
    public function company()
    {
        return $this->belongsTo('Modules\Company\CompanyCore\Models\Company', 'company_id');
    }


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
