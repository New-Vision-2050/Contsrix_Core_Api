<?php declare(strict_types=1);

namespace Modules\Subscription\Package\Models;

use Modules\Country\Models\Country;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Subscription\Enums\PeriodUnitEnum;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Company\CompanyType\Models\CompanyType;
use Modules\Company\CompanyField\Models\CompanyField;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram;

class Package extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'price',
        'currency',
        'company_access_program_id',
        'subscription_period',
        'subscription_period_unit',
        'trial_period',
        'trial_period_unit',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'subscription_period' => 'integer',
        'trial_period' => 'integer',
        'subscription_period_unit' => PeriodUnitEnum::class,
        'trial_period_unit' => PeriodUnitEnum::class,
    ];

    public function getSubscriptionPeriodUnitLabelAttribute(): ?string
    {
        return $this->subscription_period_unit?->label();
    }

    public function getTrialPeriodUnitLabelAttribute(): ?string
    {
        return $this->trial_period_unit?->label();
    }


    /**
     * Relation: Belongs to CompanyAccessProgram
     */
    public function companyAccessProgram()
    {
        return $this->belongsTo(CompanyAccessProgram::class);
    }


    /**
     * Many-to-many: countries
     */
    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(
            Country::class,
            'country_package',
            'package_id',
            'country_id'
        );
    }

    /**
     * Many-to-many: company fields
     */
    public function companyFields(): BelongsToMany
    {
        return $this->belongsToMany(
            CompanyField::class,
            'company_field_package',
            'package_id',
            'company_field_id'
        );
    }

    /**
     * Many-to-many: company types
     */
    public function companyTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            CompanyType::class,
            'company_type_package',
            'package_id',
            'company_type_id'
        );
    }

    /**
     * Get all of the features for the Module
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function features(): HasMany
    {
        return $this->hasMany(PackageFeature::class);
    }
}
