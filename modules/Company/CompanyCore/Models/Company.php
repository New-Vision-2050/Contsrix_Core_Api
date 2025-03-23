<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Company\CompanyCore\Database\factories\CompanyFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Company\CompanyField\Models\CompanyField;
use Modules\Company\CompanyType\Models\CompanyType;
use Modules\Company\CompanyRegistrationType\Models\CompanyRegistrationType;
use Modules\Country\Models\Country;
use Modules\User\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Modules\Shared\Media\MediaLibrary\CustomPathGenerator;
//use BasePackage\Shared\Traits\HasTranslations;

class Company extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use InteractsWithMedia;
    //use HasTranslations;
    // use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'user_name',
        'email',
        'phone',
        'country_id',
        'company_type_id',
        'company_field_id',
        'registration_type_id',
        'general_manager_id',
        'is_active',
        'complete_data',
        'date_activate',
        'registration_no',
        'serial_no',
        'image_path',
        'subdomain',
        'database_schema',
        'is_tenant',
        'tenant_created_at',
        'tenant_expires_at',
        'tenant_plan'
    ];
    protected $casts = [
        'id' => 'string',
        'date_activate' => 'date',
        'is_tenant' => 'boolean',
        'tenant_created_at' => 'datetime',
        'tenant_expires_at' => 'datetime'
    ];

    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function generalManager()
    {
        return $this->belongsTo(User::class, 'general_manager_id', 'id');
    }

    public function companyType()
    {
        return $this->belongsTo(CompanyType::class);
    }

    public function companyField()
    {
        return $this->belongsTo(CompanyField::class);
    }

    public function companyRegistrationType()
    {
        return $this->belongsTo(CompanyRegistrationType::class,'registration_type_id');
    }
    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $media->getFullUrl(); // Ensure this is using your custom method
    }

    /**
     * Check if the company is a tenant.
     *
     * @return bool
     */
    public function isTenant(): bool
    {
        return $this->is_tenant;
    }

    /**
     * Get the tenant's schema name.
     *
     * @return string|null
     */
    public function getSchemaName(): ?string
    {
        return $this->database_schema;
    }

    /**
     * Check if the tenant's subscription is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        if (!$this->is_tenant) {
            return false;
        }

        if (!$this->tenant_expires_at) {
            return true; // No expiration date means it's active
        }

        return $this->tenant_expires_at->isFuture();
    }

    /**
     * Get the tenant's subdomain URL.
     *
     * @return string|null
     */
    public function getSubdomainUrl(): ?string
    {
        if (!$this->subdomain) {
            return null;
        }

        $format = config('tenant.domain.tenant_domain_format', '{tenant}.example.com');
        return str_replace('{tenant}', $this->subdomain, $format);
    }

    /**
     * Get the days remaining in the tenant's subscription.
     *
     * @return int|null
     */
    public function getDaysRemaining(): ?int
    {
        if (!$this->tenant_expires_at) {
            return null;
        }

        return now()->diffInDays($this->tenant_expires_at, false);
    }

    /**
     * Extend the tenant's subscription.
     *
     * @param int $days
     * @return bool
     */
    public function extendSubscription(int $days): bool
    {
        if (!$this->is_tenant) {
            return false;
        }

        if ($this->tenant_expires_at) {
            $this->tenant_expires_at = $this->tenant_expires_at->addDays($days);
        } else {
            $this->tenant_expires_at = now()->addDays($days);
        }

        return $this->save();
    }

    /**
     * Change the tenant's plan.
     *
     * @param string $plan
     * @return bool
     */
    public function changePlan(string $plan): bool
    {
        if (!$this->is_tenant) {
            return false;
        }

        $this->tenant_plan = $plan;
        return $this->save();
    }
}
