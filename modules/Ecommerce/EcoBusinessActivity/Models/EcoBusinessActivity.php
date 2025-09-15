<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBusinessActivity\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ecommerce\EcoBusinessActivity\Database\factories\EcoBusinessActivityFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyField\Models\CompanyField;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
class EcoBusinessActivity extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use InteractsWithMedia;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'company_field_id',
        'business_name',
        'commercial_registration_number',
        'identity_number',
        'owner_name',
        'national_identity_numbers',
        'tax_certificate_number',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
    ];

    protected static function newFactory(): EcoBusinessActivityFactory
    {
        return EcoBusinessActivityFactory::new();
    }
    public function getMediaUrlsAttribute()
    {
        return $this->media->map(fn($media) => $media->getFullUrl());
    }
    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $media->getFullUrl(); // Ensure this is using your custom method
    }
    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
    public function companyField(): BelongsTo
    {
        return $this->belongsTo(CompanyField::class, 'company_field_id', 'id');
    }
}
