<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FlashDeal\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ecommerce\FlashDeal\Database\factories\FlashDealFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use BasePackage\Shared\Traits\HasTranslations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Modules\Company\CompanyCore\Models\Company;
use App\Traits\ForcedBelongsToTenant;
use Carbon\Carbon;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;

class FlashDeal extends Model  implements HasMedia
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
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];
    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('upload')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeCurrentlyActive($query)
    {
        $now = now();
        return $query->where('is_active', true)
                    ->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('is_active', true)
                    ->where('start_date', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now());
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->is_active && 
               $this->start_date <= now() && 
               $this->end_date >= now();
    }

    public function isUpcoming(): bool
    {
        return $this->is_active && $this->start_date > now();
    }

    public function isExpired(): bool
    {
        return $this->end_date < now();
    }

    /**
     * Get the status text
     */
    public function getStatusTextAttribute(): string
    {
        if (!$this->is_active) {
            return 'غير مفعل';
        }

        $now = Carbon::now();
        if ($this->start_date > $now) {
            return 'لم يبدأ بعد';
        } elseif ($this->end_date < $now) {
            return 'منتهي';
        } else {
            return 'نشط';
        }
    }

    /**
     * Get the company that owns the flash deal
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            EcoProduct::class,
            'flash_deal_product',
            'flash_deal_id',
            'product_id'
        )->withTimestamps();
    }

    protected static function newFactory(): FlashDealFactory
    {
        return FlashDealFactory::new();
    }
}
