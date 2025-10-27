<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoClient\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ecommerce\EcoClient\Database\factories\EcoClientFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Ecommerce\EcoOrder\Models\EcoOrder;
//use BasePackage\Shared\Traits\HasTranslations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Traits\ForcedBelongsToTenant;

class EcoClient extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    //use HasTranslations;
    //use SoftDeletes;
    use InteractsWithMedia;
    use ForcedBelongsToTenant;
    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'phone',
        'phone_code',
        'password',
        'gender'
    ];

    protected $casts = [
        'id' => 'string',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    protected static function newFactory(): EcoClientFactory
    {
        return EcoClientFactory::new();
    }
    public function getMediaUrlsAttribute()
    {
        return $this->media->map(fn($media) => $media->getFullUrl());
    }
    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $media->getFullUrl(); // Ensure this is using your custom method
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function orders()
    {
        return $this->hasMany(EcoOrder::class);
    }
}
