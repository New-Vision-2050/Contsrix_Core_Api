<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Feature\Models;

use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use BasePackage\Shared\Traits\HasTranslations;
use Modules\SubscriptionSystem\Modules\Models\Module;
use Modules\SubscriptionSystem\ProgramSystem\Models\ProgramSystem;

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
    public function programSystems()
    {
        return $this->belongsToMany(
            ProgramSystem::class,
            'program_system_feature'
        )->withPivot('module_id')->withTimestamps();
    }
}
