<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Company\CompanyCore\Models\Company;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Traits\ForcedBelongsToTenant;

class EcoBannerSetting extends Model implements HasMedia
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
        'banner_location',
        'banner_display_type',
        'banner_count',
        'enable_banner',
        'type_page',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'banner_count' => 'integer',
        'enable_banner' => 'boolean',
    ];

    /**
     * Company relationship
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getMediaUrlsAttribute()
    {
        return $this->media->map(fn($media) => $media->getFullUrl());
    }
    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $media->getFullUrl();
    }

    /**
     * Scopes
     */
    public function scopeForCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeEnabled($query)
    {
        return $query->where('enable_banner', true);
    }

    public function scopeByLocation($query, string $location)
    {
        return $query->where('banner_location', $location);
    }

    public function scopeByDisplayType($query, string $displayType)
    {
        return $query->where('banner_display_type', $displayType);
    }
}
