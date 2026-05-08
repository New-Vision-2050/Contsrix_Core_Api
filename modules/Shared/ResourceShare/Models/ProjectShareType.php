<?php

declare(strict_types=1);

namespace Modules\Shared\ResourceShare\Models;

use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\HasTranslations;

class ProjectShareType extends Model
{
    use HasTranslations;

    protected $fillable = [
        'name',
        'level',
        'is_active',
    ];

    protected array $translatable = ['name'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope to get only types (النوع)
     */
    public function scopeTypes($query)
    {
        return $query->where('level', 'type');
    }

    /**
     * Scope to get relations (العلاقة)
     */
    public function scopeRelations($query)
    {
        return $query->where('level', 'relation');
    }

    /**
     * Scope to get roles (الدور)
     */
    public function scopeRoles($query)
    {
        return $query->where('level', 'role');
    }

    /**
     * Scope to get only active types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
