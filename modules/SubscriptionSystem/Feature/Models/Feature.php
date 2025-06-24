<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Feature\Models;

use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
<<<<<<< HEAD:modules/Subscription/Models/Feature.php
=======
use BasePackage\Shared\Traits\HasTranslations;
use Modules\SubscriptionSystem\Modules\Models\Module;
use Modules\SubscriptionSystem\ProgramSystem\Models\ProgramSystem;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\RoleAndPermission\Models\Permission;
>>>>>>> 955f685f (merge with subscription and solve conflicts):modules/SubscriptionSystem/Feature/Models/Feature.php

class Feature extends Model
{
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    // use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        // 'name',
        'slug',
        'module_id'
    ];
    public array $translatable = ['name'];


    protected $casts = [
        'id' => 'string',
        // 'name' => 'json',
    ];

    /**
     * Get the module that owns the Feature
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
<<<<<<< HEAD:modules/Subscription/Models/Feature.php
=======
    public function programSystems()
    {
        return $this->belongsToMany(
            ProgramSystem::class,
            'program_system_feature'
        )->withPivot('module_id')->withTimestamps();
    }

    /**
     * Get the permissions associated with this feature
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'feature_permission', 'feature_id', 'permission_id')
            ->withTimestamps();
    }
>>>>>>> 955f685f (merge with subscription and solve conflicts):modules/SubscriptionSystem/Feature/Models/Feature.php
}
