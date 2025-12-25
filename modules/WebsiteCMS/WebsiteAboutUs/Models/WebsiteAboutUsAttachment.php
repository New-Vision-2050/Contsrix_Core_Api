<?php

namespace Modules\WebsiteCMS\WebsiteAboutUs\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class WebsiteAboutUsAttachment extends Model implements HasMedia
{
    use  UuidTrait, InteractsWithMedia;

    protected $table = 'website_about_us_attachments';
    public $incrementing = false;

    protected $keyType = 'string';
    protected $casts = [
        'id' => 'string',
    ];
    protected $fillable = [
        'website_about_us_id',
        'name',
    ];

    /**
     * Get the website about us that owns the attachment.
     */
    public function websiteAboutUs(): BelongsTo
    {
        return $this->belongsTo(WebsiteAboutUs::class, 'website_about_us_id');
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachment')->singleFile();
    }
}
