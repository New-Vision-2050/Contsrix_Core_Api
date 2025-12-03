<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTheme\Models;

use App\Casts\UuidCast;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\WebsiteCMS\WebsiteTheme\Database\factories\WebsiteThemeFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class WebsiteTheme extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;
    use InteractsWithMedia;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $with=["media"];

    protected $fillable = [
        'company_id',
        'url',
        'radius',
        'html_font_size',
        'font_family',
        'font_size',
        'font_weight_light',
        'font_weight_regular',
        'font_weight_medium',
        'font_weight_bold',
        'status',
    ];

    protected $casts = [
        'id' => UuidCast::class,
        'company_id' => UuidCast::class,
        'radius' => 'integer',
        'html_font_size' => 'integer',
        'status' => 'integer',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('icon')->singleFile();
    }

    /**
     * Relationship to WebsiteColorPalette
     */
    public function colorPalettes()
    {
        return $this->hasMany(WebsiteColorPalette::class, 'website_theme_id');
    }

    protected static function newFactory(): WebsiteThemeFactory
    {
        return WebsiteThemeFactory::new();
    }
}
