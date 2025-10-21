<?php

declare(strict_types=1);

namespace Modules\Shared\Media\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Modules\Shared\Media\MediaLibrary\CustomPathGenerator;
use Modules\ArchiveLibrary\File\Models\File;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomMedia extends Media
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'model_type',
        'model_id',
        'uuid',
        'collection_name',
        'name',
        'file_name',
        'mime_type',
        'disk',
        'conversions_disk',
        'size',
        'manipulations',
        'custom_properties',
        'generated_conversions',
        'responsive_images',
        'order_column',
        'file_id',
        'folder_id',
    ];

    /**
     * Get the file associated with this media.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function file()
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    /**
     * Get the owning model (polymorphic relationship).
     * 
     * This allows accessing the model that the media is attached to.
     * Usage: $media->modelable (returns User, Company, Employee, etc.)
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function modelable(): MorphTo
    {
        return $this->morphTo('model');
    }

    public function getFullUrl(string $conversionName = ''): string
    {
        return (new CustomPathGenerator)->getFullUrl($this);
    }

    /**
     * Get the full URL for the media file (original).
     *
     * @param string $conversionName
     * @return string
     */
    public function original_url(string $conversionName = ''): string
    {
        // Use the custom path generator to get the full URL
        return (new CustomPathGenerator)->getFullUrl($this);
    }
}

