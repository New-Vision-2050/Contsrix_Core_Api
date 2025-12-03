<?php

namespace Modules\WebsiteCMS\WebsiteService\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PreviousWork extends Model implements HasMedia
{
    use UuidTrait, InteractsWithMedia;

    protected $keyType="string";
    public $incrementing = false;

    protected $fillable = [
        'website_service_id',
        'description',
    ];

    public function websiteService(): BelongsTo
    {
        return $this->belongsTo(WebsiteService::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('previous_work_images')
            ->singleFile();
    }
}
