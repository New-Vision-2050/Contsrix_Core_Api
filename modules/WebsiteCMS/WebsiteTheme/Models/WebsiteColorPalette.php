<?php

namespace Modules\WebsiteCMS\WebsiteTheme\Models;

use App\Casts\UuidCast;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class WebsiteColorPalette extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'website_color_palettes';

    protected $fillable = [
        'website_theme_id',
        'name',
        'slug',
        'primary',
        'light',
        'dark',
        'contrast',
    ];

    protected $casts = [
        'id' => UuidCast::class,
        'website_theme_id' => UuidCast::class,
    ];

    public function getTenantIdColumn(): string
    {
        return 'website_theme_id';
    }

    /**
     * Relationship to WebsiteTheme
     */
    public function websiteTheme()
    {
        return $this->belongsTo(WebsiteTheme::class, 'website_theme_id');
    }
}
