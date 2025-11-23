<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\SocialMediaLink\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\WebsiteCMS\SocialMediaLink\Database\factories\SocialMediaLinkFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\WebsiteCMS\SocialMediaLink\Enums\SocialMediaType;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class SocialMediaLink extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;
    use InteractsWithMedia;

    public $incrementing = false;

    protected $keyType = 'string';
    protected $table = "website_social_media_links";

    protected $fillable = [
        'type',
        'link',
        'status',
        'company_id',
    ];

    protected $casts = [
        'id' => 'string',
        'type' => SocialMediaType::class,
        'status' => 'integer',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('icon')->singleFile();
    }

    protected static function newFactory(): SocialMediaLinkFactory
    {
        return SocialMediaLinkFactory::new();
    }
}
