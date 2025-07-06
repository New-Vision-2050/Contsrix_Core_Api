<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Models;

use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
<<<<<<< HEAD
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Permission as SpatiePermission;
=======
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Subscription\Models\Feature;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Stancl\Tenancy\Database\Concerns\BelongsToPrimaryModel;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
>>>>>>> 4d33c9eb (merge roles with subscription)

class Permission extends SpatiePermission
{
    use UuidTrait;
    use BaseFilterable;
    use HasFactory;
<<<<<<< HEAD
=======
    use BelongsToTenant;
>>>>>>> 4d33c9eb (merge roles with subscription)

    public array $translatable = [];

    public $incrementing = false;
    protected $primaryKey = 'id';
    protected $keyType = 'string';

<<<<<<< HEAD
    // Allow mass assignment for new columns
    protected $fillable = [
        'name',
        'guard_name',
        'resource',
        'action',
        'program_id',
        'sub_entity_id',
    ];
=======

>>>>>>> 4d33c9eb (merge roles with subscription)

    /**
     * Relation to Program model (nullable).
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(\Modules\Program\Models\Program::class, 'program_id');
    }
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
     * Relation to SubEntity model (nullable).
     */
    public function subEntity(): BelongsTo
    {
<<<<<<< HEAD
=======
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
>>>>>>> 4d33c9eb (merge roles with subscription)
        return $this->belongsTo(\Modules\SubEntity\Models\SubEntity::class, 'sub_entity_id');
    }
}
