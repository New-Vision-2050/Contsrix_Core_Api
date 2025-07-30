<?php

declare(strict_types=1);

namespace Modules\SubEntity\Models;

use Illuminate\Support\Str;
use Modules\Program\Models\Program;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\RoleAndPermission\Models\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\SubEntity\Database\factories\SubEntityFactory;

class SubEntity extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;

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
        'origin_super_entity',
        'slug',
        'registration_form_id'
    ];

    protected $casts = [
        'id' => 'string',
        'default_attributes' => 'json',
        'optional_attributes' => 'json',
    ];

    public const PERMISSION_ACTIONS = [
        'activate',
        'create',
        'update',
        'delete',
        'list',
        'view',
        'export',
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

        static::created(function (self $subEntity) {
            $subEntity->createDefaultPermissions();
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

    public function children()
    {
        return $this->hasMany(self::class, 'super_entity')->where('is_active', true);
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

    protected function createDefaultPermissions(): void
    {
        if (!$this->mainProgram || !$this->slug) {
            return;
        }

        $module = $this->mainProgram->slug;
        $resource = $this->name . '*' . $this->id;
        $createdPermissions = [];

        foreach (self::PERMISSION_ACTIONS as $action) {
            $permission = Permission::firstOrCreate([
                'name' => "{$module}.{$resource}.{$action}",
                "key" => "dynamic-".$this->slug.".$action",

            ], [
                'status' => true,
            ]);


                $createdPermissions[] = $permission;

        }

        // Auto-assign new permissions to main package and super-admin role
        if (!empty($createdPermissions)) {
            $this->assignPermissionsToMainPackageAndSuperAdmin($createdPermissions);
        }
    }

    /**
     * Assign permissions to main package and super-admin role
     *
     * @param array $permissions
     * @return void
     */
    protected function assignPermissionsToMainPackageAndSuperAdmin(array $permissions): void
    {
        try {
            \DB::transaction(function () use ($permissions) {
                // Assign to main package
                $this->assignPermissionsToMainPackage($permissions);

                // Assign to super-admin role
                $this->assignPermissionsToSuperAdminRole($permissions);
            });
        } catch (\Exception $e) {
            \Log::error('Failed to auto-assign permissions to main package and super-admin role', [
                'error' => $e->getMessage(),
                'permissions' => array_map(fn($p) => $p->id, $permissions),
                'sub_entity' => $this->id
            ]);
        }
    }

    /**
     * Assign permissions to main package
     *
     * @param array $permissions
     * @return void
     */
    protected function assignPermissionsToMainPackage(array $permissions): void
    {
        $mainPackage = \Modules\Subscription\Package\Models\Package::where('name', 'Main Package')->first();

        if (!$mainPackage) {
            \Log::warning('Main Package not found for auto-assignment', [
                'sub_entity' => $this->id
            ]);
            return;
        }

        // Get current permissions
        $currentPermissions = $mainPackage->permissions()->pluck('permissions.id')->toArray();

        // Prepare sync data - keep existing permissions and add new ones
        $syncData = [];

        // Keep existing permissions with their current pivot data
        foreach ($mainPackage->permissions as $existingPermission) {
            $syncData[$existingPermission->id] = [
                'limit' => $existingPermission->pivot->limit
            ];
        }

        // Add new permissions without limit (null)
        foreach ($permissions as $permission) {
            if (!in_array($permission->id, $currentPermissions)) {
                $syncData[$permission->id] = [
                    'limit' => null
                ];
            }
        }

        // Sync permissions
        $mainPackage->permissions()->sync($syncData);

        \Log::info('Auto-assigned permissions to Main Package', [
            'package_id' => $mainPackage->id,
            'new_permissions' => array_map(fn($p) => $p->id, $permissions),
            'sub_entity' => $this->id
        ]);
    }

    /**
     * Assign permissions to super-admin role
     *
     * @param array $permissions
     * @return void
     */
    protected function assignPermissionsToSuperAdminRole(array $permissions): void
    {
        $superAdminRole = \Modules\RoleAndPermission\Models\Role::where('name', 'super-admin')
            ->where('company_id', tenant('company_id'))
            ->first();

        if (!$superAdminRole) {
            \Log::warning('Super-admin role not found for auto-assignment', [
                'company_id' => tenant('company_id'),
                'sub_entity' => $this->id
            ]);
            return;
        }

        // Get current role permissions
        $currentPermissions = $superAdminRole->permissions()->pluck('permissions.id')->toArray();

        // Add new permissions to the role
        $permissionsToAdd = [];
        foreach ($permissions as $permission) {
            if (!in_array($permission->id, $currentPermissions)) {
                $permissionsToAdd[] = $permission->id;
            }
        }

        if (!empty($permissionsToAdd)) {
            // Attach new permissions (keeping existing ones)
            $superAdminRole->permissions()->attach($permissionsToAdd);

            \Log::info('Auto-assigned permissions to super-admin role', [
                'role_id' => $superAdminRole->id,
                'new_permissions' => $permissionsToAdd,
                'sub_entity' => $this->id
            ]);
        }
    }
}
