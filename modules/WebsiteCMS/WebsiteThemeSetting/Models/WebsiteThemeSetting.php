<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteThemeSetting\Models;

use App\Casts\UuidCast;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\WebsiteCMS\WebsiteThemeSetting\Database\factories\WebsiteThemeSettingFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class WebsiteThemeSetting extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    use InteractsWithMedia;

    protected array $translatable = ['title', 'description', 'about'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'title',
        'description',
        'about',
        'is_default',
        'status',
    ];

    protected $casts = [
        'id' => UuidCast::class,
        'status' => 'integer',
    ];

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('main_image')->singleFile();
    }

    /**
     * Relationship to WebsiteThemeSettingDepartment
     */
    public function departments()
    {
        return $this->hasMany(WebsiteThemeSettingDepartment::class, 'website_theme_setting_id');
    }

    /**
     * Relationship to companies through pivot table
     */
    public function companies()
    {
        return $this->belongsToMany(
            \Modules\Company\CompanyCore\Models\Company::class,
            'company_website_theme_settings',
            'website_theme_setting_id',
            'company_id'
        )->withTimestamps()->withPivot('assigned_at');
    }

    protected static function newFactory(): WebsiteThemeSettingFactory
    {
        return WebsiteThemeSettingFactory::new();
    }
}
