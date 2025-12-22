<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAboutUs\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\WebsiteCMS\WebsiteAboutUs\Database\factories\WebsiteAboutUsFactory;
use Modules\WebsiteCMS\WebsiteIcon\Models\WebsiteIcon;
use Modules\WebsiteCMS\WebsiteIcon\Enums\WebsiteIconCategoryType;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class WebsiteAboutUs extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    use BelongsToTenant;
    use InteractsWithMedia;

    protected $table = 'website_about_us';

    protected array $translatable = [
        'about_me',
        'vision',
        'target',
        'slogan',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'title',
        'description',
        'is_certificates',
        'is_approvals',
        'is_companies',
        'about_me',
        'vision',
        'target',
        'slogan',
        'status',
    ];

    protected $casts = [
        'id' => 'string',

        'status' => 'integer',
    ];

    /**
     * Get the tenant ID column name.
     */
    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    /**
     * Get the project types for the website about us.
     */
    public function projectTypes(): HasMany
    {
        return $this->hasMany(WebsiteAboutUsProjectType::class, 'website_about_us_id');
    }

    /**
     * Get the attachments for the website about us.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(WebsiteAboutUsAttachment::class, 'website_about_us_id');
    }

    /**
     * Get certificate icons for the website about us.
     */
    public function certificateIcons(): HasMany
    {
        return $this->hasMany(WebsiteIcon::class, 'company_id', 'company_id')
            ->where('website_icon_category_type', WebsiteIconCategoryType::CERTIFICATES->value);
    }

    /**
     * Get approval icons for the website about us.
     */
    public function approvalIcons(): HasMany
    {
        return $this->hasMany(WebsiteIcon::class, 'company_id', 'company_id')
            ->where('website_icon_category_type', WebsiteIconCategoryType::APPROVALS->value);
    }

    /**
     * Get company icons for the website about us.
     */
    public function companyIcons(): HasMany
    {
        return $this->hasMany(WebsiteIcon::class, 'company_id', 'company_id')
            ->where('website_icon_category_type', WebsiteIconCategoryType::COMPANIES->value);
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('main_image')->singleFile();
    }

    protected static function newFactory(): WebsiteAboutUsFactory
    {
        return WebsiteAboutUsFactory::new();
    }
}
