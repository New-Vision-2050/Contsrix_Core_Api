<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ecommerce\EcoProduct\Database\factories\EcoProductFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EcoProduct extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    //use SoftDeletes; // Uncomment if you intend to use soft deletes

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
        // No need to cast 'name' or 'description' as they are handled by HasTranslations
    ];

    protected static function newFactory(): EcoProductFactory
    {
        return EcoProductFactory::new();
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

    public function seo(): HasMany // Or HasOne if you only allow one SEO entry per product
    {
        return $this->hasMany(ProductSEO::class, 'product_id', 'id');
    }
}
