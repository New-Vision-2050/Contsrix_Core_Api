<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\CategoryWebsiteCMS\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Database\factories\CategoryWebsiteCMSFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;
use Modules\Company\CompanyCore\Models\Company;
use Modules\WebsiteCMS\WebsiteIcon\Models\WebsiteIcon;
use Modules\WebsiteCMS\WebsiteNews\Models\WebsiteNews;
use Modules\WebsiteCMS\WebsiteService\Models\WebsiteService;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class CategoryWebsiteCMS extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;
    use HasTranslations;

    public array $translatable = ['name'];

    protected $table = 'category_website_cms';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'category_type',
        'company_id',
    ];

    protected $casts = [
        'id' => 'string',
        'name' => 'array',
    ];


    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function websiteServices()
    {
        return $this->hasMany(WebsiteService::class, "category_website_cms_id", "id");

    }

    public function websiteNews()
    {
        return $this->hasMany(WebsiteNews::class, "category_website_cms_id", "id");
    }

    protected static function newFactory(): CategoryWebsiteCMSFactory
    {
        return CategoryWebsiteCMSFactory::new();
    }
}
