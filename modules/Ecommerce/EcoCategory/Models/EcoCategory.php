<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ecommerce\EcoCategory\Database\factories\EcoCategoryFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Traits\ForcedBelongsToTenant;

class EcoCategory extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    use InteractsWithMedia;
    use ForcedBelongsToTenant;
    //use SoftDeletes;

    public array $translatable = ['name'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'name',
        'parent_id',
        'priority',
        'is_active'
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): EcoCategoryFactory
    {
        return EcoCategoryFactory::new();
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('upload')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    public function parent()
    {
        return $this->belongsTo(EcoCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(EcoCategory::class, 'parent_id');
    }
    public function products()
    {
        return $this->hasMany(EcoProduct::class, 'category_id');
    }
}
