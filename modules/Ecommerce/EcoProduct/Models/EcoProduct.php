<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ecommerce\EcoProduct\Database\factories\EcoProductFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Ecommerce\EcoBrand\Models\EcoBrand;
use Modules\Ecommerce\EcoCategory\Models\EcoCategory;
use Modules\Ecommerce\Warehous\Models\Warehous;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Country\Models\Country;
use Modules\Ecommerce\EcoProduct\Models\ProductTax;
use Modules\Ecommerce\EcoProduct\Models\ProductDetail;
use Modules\Ecommerce\EcoProduct\Models\ProductCustomField;
use Modules\Ecommerce\EcoProduct\Models\ProductSEO;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Traits\ForcedBelongsToTenant;
class EcoProduct extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    //use SoftDeletes; // Uncomment if you intend to use soft deletes
    use InteractsWithMedia;
    use ForcedBelongsToTenant;
    protected $table = 'eco_products'; // Explicitly define table name for clarity

    public array $translatable = ['name', 'description'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'name', // String field
        'description', // Text field
        'category_id',
        'sub_category_id',
        'sub_sub_category_id',
        'brand_id',
        // countries handled via pivot table
        'type',
        'unit',
        'sku',
        'warehouse_id',
        'gender',
        'price',
        'min_order_quantity',
        'stock',
        'discount_type',
        'discount_value',
        'vat_percentage',
        'price_includes_vat',
        'shipping_amount',
        'shipping_included_in_price',
        'is_visible',
        'main_photo', // JSON field
        'other_photos', // JSON array
        'video_url', // Video URL field
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'id' => 'string',
        // countries handled via pivot table relationship
        'price' => 'float',
        'min_order_quantity' => 'integer',
        'stock' => 'integer',
        'discount_value' => 'float',
        'vat_percentage' => 'float',
        'shipping_amount' => 'float',
        'price_includes_vat' => 'boolean',
        'shipping_included_in_price' => 'boolean',
        'is_visible' => 'boolean',
        'main_photo' => 'array', // JSON field
        'other_photos' => 'array', // JSON array
    ];

    protected static function newFactory(): EcoProductFactory
    {
        return EcoProductFactory::new();
    }
    public function getMediaUrlsAttribute()
    {
        return $this->media->map(fn($media) => $media->getFullUrl());
    }
    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->width(300)
            ->height(300)
            ->sharpen(10);

        $this->addMediaConversion('preview')
            ->width(800)
            ->height(600)
            ->sharpen(10);
    }
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehous::class, 'warehouse_id', 'id');
    }

    /**
     * Get the products associated with this product.
     */
    public function associatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(
            EcoProduct::class,
            'product_product', // Pivot table name
            'product_id',      // Foreign key on the pivot table for THIS product
            'related_product_id' // Foreign key on the pivot table for the RELATED product
        );
    }

    /**
     * Get the products that this product is associated with.
     * Useful if you want to find products that list *this* product as an associated item.
     */
    public function inverseAssociatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(
            EcoProduct::class,
            'product_product',
            'related_product_id', // Foreign key on the pivot table for THIS product (as a related item)
            'product_id'          // Foreign key on the pivot table for the MAIN product
        );
    }
    public function category(): BelongsTo
    {
        return $this->belongsTo(EcoCategory::class, 'category_id', 'id');
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(EcoCategory::class, 'sub_category_id', 'id');
    }

    public function subSubCategory(): BelongsTo
    {
        return $this->belongsTo(EcoCategory::class, 'sub_sub_category_id', 'id');
    }
    public function brand(): BelongsTo
    {
        return $this->belongsTo(EcoBrand::class, 'brand_id', 'id');
    }

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'product_countries', 'product_id', 'country_id');
    }

    /**
     * Calculate final price after applying discounts
     */
    public function getFinalPriceAttribute(): float
    {
        if (!$this->has_discount || !$this->getIsOnDiscountAttribute()) {
            return $this->price;
        }

        $discountAmount = 0;

        if ($this->discount_percentage) {
            $discountAmount = $this->price * ($this->discount_percentage / 100);
        } elseif ($this->discount_amount) {
            $discountAmount = $this->discount_amount;
        }

        // Apply max discount limit if set
        if ($this->max_discount_amount && $discountAmount > $this->max_discount_amount) {
            $discountAmount = $this->max_discount_amount;
        }

        return max(0, $this->price - $discountAmount);
    }

    /**
     * Check if product is currently on discount
     */
    public function getIsOnDiscountAttribute(): bool
    {
        if (!$this->has_discount) {
            return false;
        }

        $now = now();

        if ($this->discount_start_date && $now < $this->discount_start_date) {
            return false;
        }

        if ($this->discount_end_date && $now > $this->discount_end_date) {
            return false;
        }

        return true;
    }

    /**
     * Check if product is in stock
     */
    public function getIsInStockAttribute(): bool
    {
        if ($this->unlimited_quantity) {
            return true;
        }

        return $this->stock > 0;
    }

    /**
     * Get main product image URL
     */
    public function getMainImageAttribute(): ?string
    {
        $mainImage = $this->getFirstMedia();
        return $mainImage ? $mainImage->getUrl() : null;
    }

}
