<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShop\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ecommerce\EcoShop\Database\factories\EcoShopFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use Modules\Ecommerce\EcoOrder\Models\EcoOrder;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class EcoShop extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    // use HasTranslations;
    use InteractsWithMedia;
    //use SoftDeletes;

    // public array $translatable = ['name'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'email',
        'phone',
        'website_url',
        'facebook_url',
        'instagram_url',
        'twitter_url',
        'tiktok_url',
        'snapchat_url',
        'whatsapp_number',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
    ];

    protected static function newFactory(): EcoShopFactory
    {
        return EcoShopFactory::new();
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }


    public function getWhatsappLinkAttribute(): ?string
    {
        if (!$this->whatsapp_number) {
            return null;
        }

        $number = preg_replace('/[^0-9]/', '', $this->whatsapp_number);
        if (strlen($number) === 9 && substr($number, 0, 1) === '5') {
            $number = '966' . $number;
        }

        return "https://wa.me/{$number}";
    }
}
