<?php

declare(strict_types=1);

namespace Modules\AdminRequest\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\BaseFilterable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

// use BasePackage\Shared\Traits\HasTranslations;

class AdminRequestTransaction extends Model implements HasMedia
{
    use UuidTrait;
    use BaseFilterable;
    use InteractsWithMedia;
    // use HasTranslations;
    // use SoftDeletes;

    public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        "admin_request_id",
        "requestable_id",
        "requestable_type",
        "action",
        "data",
        "status",
    ];

    public function getMediaUrlsAttribute()
    {
        return $this->media->map(fn($media) => $media->getFullUrl());
    }

    public function requestable()
    {
        return $this->morphTo();
    }


    protected $casts = [
        'id' => 'string',
        "data" => "array"
    ];
}
