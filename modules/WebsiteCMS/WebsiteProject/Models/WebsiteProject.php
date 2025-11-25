<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProject\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\WebsiteCMS\WebsiteProject\Database\factories\WebsiteProjectFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Modules\WebsiteCMS\WebsiteProjectSetting\Models\WebsiteProjectSetting;
use Modules\WebsiteCMS\WebsiteService\Models\WebsiteService;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class WebsiteProject extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    use BelongsToTenant;
    use InteractsWithMedia;

    public array $translatable = ['title', 'name', 'description'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'website_project_setting_id',
        'company_id',
        'status',
        'title',
        'name',
        'description',
    ];

    protected $casts = [
        'id' => 'string',
        'website_project_setting_id' => 'string',
        'company_id' => 'string',
        'status' => 'integer',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('main_image')->singleFile();
        $this->addMediaCollection('secondary_image')->singleFile();
    }

    // Relationships
    public function websiteProjectSetting()
    {
        return $this->belongsTo(WebsiteProjectSetting::class, 'website_project_setting_id');
    }

    public function projectDetails()
    {
        return $this->hasMany(WebsiteProjectDetail::class, 'website_project_id');
    }

    public function services()
    {
        return $this->belongsToMany(
            WebsiteService::class,
            'website_project_details',
            'website_project_id',
            'website_service_id'
        )->withPivot('id')->withTimestamps();
    }

    protected static function newFactory(): WebsiteProjectFactory
    {
        return WebsiteProjectFactory::new();
    }
}
