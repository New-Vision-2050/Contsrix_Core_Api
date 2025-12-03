<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteThemeSetting\Models;

use App\Casts\UuidCast;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use BasePackage\Shared\Traits\HasTranslations;

class WebsiteThemeSettingDepartment extends Model
{
    use HasFactory;
    use UuidTrait;
    use HasTranslations;

    protected $table = 'website_theme_setting_departments';

    protected array $translatable = ['name'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'website_theme_setting_id',
        'name',
    ];

    protected $casts = [
        'id' => UuidCast::class,
        'website_theme_setting_id' => UuidCast::class,
    ];

    /**
     * Relationship to WebsiteThemeSetting
     */
    public function websiteThemeSetting()
    {
        return $this->belongsTo(WebsiteThemeSetting::class, 'website_theme_setting_id');
    }
}
