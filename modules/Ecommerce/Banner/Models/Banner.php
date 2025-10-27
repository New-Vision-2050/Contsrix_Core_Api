<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ecommerce\Banner\Database\factories\BannerFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\ForcedBelongsToTenant;
class Banner extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use InteractsWithMedia;
    use ForcedBelongsToTenant;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'setting_page_id',
        'url',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'is_active' => 'boolean',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('banner_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    // Relationships
    public function settingPage(): BelongsTo
    {
        return $this->belongsTo(SettingPage::class);
    }

    protected static function newFactory(): BannerFactory
    {
        return BannerFactory::new();
    }
}
