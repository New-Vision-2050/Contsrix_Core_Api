<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteIcon\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\WebsiteCMS\WebsiteIcon\Database\factories\WebsiteIconFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Models\CategoryWebsiteCMS;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class WebsiteIcon extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    use BelongsToTenant;
    use InteractsWithMedia;

    protected array $translatable = ['name'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'category_website_cms_id',
        'company_id',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'name' => 'array',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CategoryWebsiteCMS::class, 'category_website_cms_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('icon')->singleFile();
    }

    protected static function newFactory(): WebsiteIconFactory
    {
        return WebsiteIconFactory::new();
    }
}
