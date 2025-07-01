<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Package\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Company\BusinessType\Models\BusinessType;
use Modules\Country\Models\Country;
use Modules\SubscriptionSystem\Package\Database\factories\PackageFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\SubscriptionSystem\ProgramSystem\Models\ProgramSystem;

class Package extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    //use SoftDeletes;

    public array $translatable = ['name'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [

        'price',
        'currency_id',
        'billing_cycle',
        'trial_period',
        'trial_period_type',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'currency_id' => 'string',
        'price' => 'decimal:2',
        'trial_period' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): PackageFactory
    {
        return PackageFactory::new();
    }
    public function businessTypes(): BelongsToMany
    {
        return $this->belongsToMany(BusinessType::class, 'business_type_package');
    }
    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'country_package');
    }

    public function programSystems(): BelongsToMany
    {
        return $this->belongsToMany(ProgramSystem::class, 'program_system_package');
    }
}
