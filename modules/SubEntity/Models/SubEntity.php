<?php

declare(strict_types=1);

namespace Modules\SubEntity\Models;

use Illuminate\Support\Str;
use Modules\Program\Models\Program;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\SubEntity\Database\factories\SubEntityFactory;

class SubEntity extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'icon',
        'is_active',
        'main_program_id',
        'default_attributes',
        'optional_attributes',
        'is_registrable',
        'super_entity',
        'company_id',
        'origin_super_entity'
    ];

    protected $casts = [
        'id' => 'string',
        'default_attributes' => 'json',
        'optional_attributes' => 'json',
    ];

    public function mainProgram()
    {
        return $this->belongsTo(Program::class, 'main_program_id');
    }

    protected static function newFactory(): SubEntityFactory
    {
        return SubEntityFactory::new();
    }

    public function getAttributesCountAttribute(): int
    {
        return is_array($this->optional_attributes) ? count($this->optional_attributes) : 0;
    }

    /**
     * Scope a query to only include active sub-entities.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function parentSubEntity(): BelongsTo
    {
        return $this->belongsTo(SubEntity::class, 'super_entity');
    }

    public function getOriginSuperEntityName(): string
    {
        $current = $this;

        while (Str::isUuid($current->super_entity)) {
            $parent = $current->parentSubEntity;

            if (!$parent) {
                break;
            }

            if (!Str::isUuid($parent->super_entity)) {
                return $parent->super_entity;
            }

            $current = $parent;
        }

        return $current->super_entity;
    }
}
