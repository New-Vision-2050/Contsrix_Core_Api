<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteNews\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\WebsiteCMS\WebsiteNews\Database\factories\WebsiteNewsFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;
use Modules\Company\CompanyCore\Models\Company;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Models\CategoryWebsiteCMS;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class WebsiteNews extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    use BelongsToTenant;
    use InteractsWithMedia;

    protected array $translatable = ['title', 'content'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'category_website_cms_id',
        'company_id',
        'publish_date',
        'end_date',
        'status',
        'title',
        'content',
    ];

    protected $casts = [
        'id' => 'string',
        'publish_date' => 'date',
        'end_date' => 'date',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CategoryWebsiteCMS::class, 'category_website_cms_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('main_image')
            ->singleFile();

        $this->addMediaCollection('thumbnail')
            ->singleFile();
    }

    protected static function newFactory(): WebsiteNewsFactory
    {
        return WebsiteNewsFactory::new();
    }
}
