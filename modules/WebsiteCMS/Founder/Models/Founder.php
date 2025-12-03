<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\Founder\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\WebsiteCMS\Founder\Database\factories\FounderFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;
use Modules\Company\CompanyCore\Models\Company;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Founder extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    use BelongsToTenant;
    use InteractsWithMedia;

    protected array $translatable = ['name', 'description', 'job_title'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'status',
        'name',
        'description',
        'job_title',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('personal_photo')
            ->singleFile();
    }

    protected static function newFactory(): FounderFactory
    {
        return FounderFactory::new();
    }
}
