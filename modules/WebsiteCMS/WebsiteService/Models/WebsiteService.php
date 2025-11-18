<?php

namespace Modules\WebsiteCMS\WebsiteService\Models;

use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Company\CompanyCore\Models\Company;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Models\CategoryWebsiteCMS;
use phpseclib3\Common\Functions\Strings;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class WebsiteService extends Model implements HasMedia
{
    use UuidTrait, HasTranslations, BelongsToTenant, InteractsWithMedia,BaseFilterable;


    protected $primaryKey = "id";
    protected $keyType = 'string';

    public $incrementing = false;


    protected $fillable = [
        'category_website_cms_id',
        'reference_number',
        'company_id',
        'status',
        "name",
        "description"
    ];


    protected array $translatable = ['name', 'description'];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CategoryWebsiteCMS::class, 'category_website_cms_id');
    }

    public function previousWorks(): HasMany
    {
        return $this->hasMany(PreviousWork::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('main_image')
            ->singleFile();

        $this->addMediaCollection('icon')
            ->singleFile();
    }
}
