<?php

declare(strict_types=1);

namespace Modules\UserInfo\JobOffer\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\UserInfo\JobOffer\Database\factories\JobOfferFactory;
use BasePackage\Shared\Traits\BaseFilterable;
//use BasePackage\Shared\Traits\HasTranslations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
class JobOffer extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use InteractsWithMedia;
    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'global_id',
        'job_offer_number',
        'date_send',
        'date_accept',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): JobOfferFactory
    {
        return JobOfferFactory::new();
    }
    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $media->getFullUrl();
    }
}
