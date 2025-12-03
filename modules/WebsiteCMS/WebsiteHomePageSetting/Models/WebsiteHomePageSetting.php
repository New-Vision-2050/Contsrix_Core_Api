<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePageSetting\Models;

use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Database\factories\WebsiteHomePageSettingFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class WebsiteHomePageSetting extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    use BelongsToTenant;
    use InteractsWithMedia;

    public array $translatable = ['description'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'web_video_link',
        'mobile_video_link',
        'description',
        'is_companies',
        'is_approvals',
        'is_certificates',
        'status',
    ];

    protected $casts = [
        'id' => 'string',

        'status' => 'integer',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('web_video_file')->singleFile();
        $this->addMediaCollection('mobile_video_file')->singleFile();
        $this->addMediaCollection('video_profile_file')->singleFile();
    }

    protected static function newFactory(): WebsiteHomePageSettingFactory
    {
        return WebsiteHomePageSettingFactory::new();
    }
}
