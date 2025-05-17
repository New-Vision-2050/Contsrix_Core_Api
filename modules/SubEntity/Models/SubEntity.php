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
        'origin_super_entity',
        'slug',
        'registration_form_id'
    ];

    protected $casts = [
        'id' => 'string',
        'default_attributes' => 'json',
        'optional_attributes' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $subEntity) {
            if (isset($subEntity->name) && blank($subEntity->slug)) {
                $subEntity->slug = static::generateUniqueSlug($subEntity->name);
            }
        });

        static::updating(function (self $subEntity) {
            if ($subEntity->isDirty('name') && isset($subEntity->name) && blank($subEntity->slug)) {
                $subEntity->slug = static::generateUniqueSlug($subEntity->name, $subEntity->id);
            }
        });
    }

    protected static function generateUniqueSlug(string $name, $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;

        $query = static::query()
            ->where('slug', 'LIKE', "{$baseSlug}%");

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $count = $query->count();

        if ($count > 0) {
            $slug = "{$baseSlug}-" . ($count + 1);
        }

        return $slug;
    }

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

    public function registrationForm()
    {
        return $this->belongsTo(RegistrationForm::class, 'registration_form_id');
    }

    /**
     * Return allowed registration forms: Which registration forms could be chosed from to create a child sub-entity
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<RegistrationForm, SubEntity>
     */
    public function allowedChildForms()
    {
        return $this->belongsToMany(
            \Modules\SubEntity\Models\RegistrationForm::class,
            'sub_entity_registration_form',
            'sub_entity_id',
            'registration_form_id'
        );
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
