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
use Modules\Company\CompanyCore\Models\Company;
use Modules\Ecommerce\EcoBrand\Models\EcoBrand;
use Modules\Ecommerce\EcoCategory\Models\EcoCategory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class EcoProduct extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    //use SoftDeletes; // Uncomment if you intend to use soft deletes
    use InteractsWithMedia;
    protected $table = 'eco_products'; // Explicitly define table name for clarity

    public array $translatable = ['name', 'description'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'price',
        'sku',
        'stock',
        'warehouse_id',
        'requires_shipping',
        'unlimited_quantity',
        'is_taxable',
        'price_includes_vat',
        'vat_percentage',
        'is_visible',

        // Discount fields
        'has_discount',
        'discount_amount',
        'discount_percentage',
        'discount_start_date',
        'max_discount_amount',
        'discount_end_date',
        // Translatable fields must also be in fillable for spatie/laravel-translatable to work
        'name',
        'description',
        'category_id',
        'sub_category_id',
        'brand_id',
        'type',
    ];

    protected $casts = [
        'id' => 'string',
        'price' => 'float',
        'stock' => 'integer',
        'requires_shipping' => 'boolean',
        'unlimited_quantity' => 'boolean',
        'is_taxable' => 'boolean',
        'price_includes_vat' => 'boolean',
        'vat_percentage' => 'float',
        'is_visible' => 'boolean',
        'has_discount' => 'boolean',
        'discount_amount' => 'float',
        'discount_percentage' => 'float',
        'discount_start_date' => 'datetime',
        'discount_end_date' => 'datetime',
        // No need to cast 'name' or 'description' as they are handled by HasTranslations
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
        $media->getFullUrl(); // Ensure this is using your custom method
    }
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
    // Define relationships
    public function taxes(): HasMany
    {
        return $this->hasMany(ProductTax::class, 'product_id', 'id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(ProductDetail::class, 'product_id', 'id');
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(ProductCustomField::class, 'product_id', 'id');
    }

    public function seo(): HasOne
    {
        return $this->hasOne(ProductSEO::class, 'product_id', 'id');
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
    public function brand(): BelongsTo
    {
        return $this->belongsTo(EcoBrand::class, 'brand_id', 'id');
    }
}
