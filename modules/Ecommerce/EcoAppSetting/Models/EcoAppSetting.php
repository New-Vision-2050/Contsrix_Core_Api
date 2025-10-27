<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Ecommerce\EcoAppSetting\Database\factories\EcoAppSettingFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Company\CompanyCore\Models\Company;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Traits\ForcedBelongsToTenant;

class EcoAppSetting extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use InteractsWithMedia;
    use ForcedBelongsToTenant;
    
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',

        // Theme & UI Settings
        'background_color',
        'enable_search',

        // First page settings
        'show_logo_on_first_page',

        // Front page settings
        'show_logo_on_front_page',
        'count_photos',

        // Display products
        'product_display_category',
        'product_display_type',
        'product_columns_count',
        'product_rows_count',
        'show_products_in_app',

        // Display favorites
        'show_favorites_search',
        'show_favorites_delete',
        'show_favorites_products',
        'favorites_display_type',
        'show_favorites_in_app',

        // Product selection settings
        'show_product_image',
        'show_product_rating',
        'show_similar_products',
        'show_product_price',
        'show_product_shipping',
        'show_product_description',
        'show_product_color_code',
        'show_product_size',
        'show_product_comment',
        'can_product_comment',

        // Cart settings
        'show_cart_products',
        'cart_display_type',
        'cart_columns_count',
        'show_cart_in_app',

        // Product card settings
        'show_product_name',
        'show_product_description_card',
        'show_product_price_card',
        'show_product_color',
        'show_product_size_card',
        'show_similar_products_card',
        'product_card_display_type',
        'product_card_columns_count',
        'show_discount_code',
        'show_payment_details',
        'show_product_card_in_app',

        // Filter settings
        'show_filter_in_app',
        'show_category_filter',
        'show_product_filter',
        'show_color_filter',
        'show_brand_filter',
        'show_size_filter',
        'show_price_filter',
        'show_rating_filter',
        'show_discount_filter',

        // Terms and Conditions settings
        'show_terms_text',
        'show_privacy_policy',
        'show_return_policy',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',

        // Boolean fields
        'enable_search' => 'boolean',
        'show_logo_on_first_page' => 'boolean',
        'show_logo_on_front_page' => 'boolean',
        'show_products_in_app' => 'boolean',
        'show_favorites_search' => 'boolean',
        'show_favorites_delete' => 'boolean',
        'show_favorites_products' => 'boolean',
        'show_favorites_in_app' => 'boolean',
        'show_product_image' => 'boolean',
        'show_product_rating' => 'boolean',
        'show_similar_products' => 'boolean',
        'show_product_price' => 'boolean',
        'show_product_shipping' => 'boolean',
        'show_product_description' => 'boolean',
        'show_product_color_code' => 'boolean',
        'show_product_size' => 'boolean',
        'show_product_comment' => 'boolean',
        'can_product_comment' => 'boolean',
        'show_cart_products' => 'boolean',
        'show_cart_in_app' => 'boolean',
        'show_product_name' => 'boolean',
        'show_product_description_card' => 'boolean',
        'show_product_price_card' => 'boolean',
        'show_product_color' => 'boolean',
        'show_product_size_card' => 'boolean',
        'show_similar_products_card' => 'boolean',
        'show_discount_code' => 'boolean',
        'show_payment_details' => 'boolean',
        'show_product_card_in_app' => 'boolean',
        'show_filter_in_app' => 'boolean',
        'show_category_filter' => 'boolean',
        'show_product_filter' => 'boolean',
        'show_color_filter' => 'boolean',
        'show_brand_filter' => 'boolean',
        'show_size_filter' => 'boolean',
        'show_price_filter' => 'boolean',
        'show_rating_filter' => 'boolean',
        'show_discount_filter' => 'boolean',
        'show_terms_text' => 'boolean',
        'show_privacy_policy' => 'boolean',
        'show_return_policy' => 'boolean',

        // Integer fields
        'count_photos' => 'integer',
        'product_columns_count' => 'integer',
        'product_rows_count' => 'integer',
        'cart_columns_count' => 'integer',
        'product_card_columns_count' => 'integer',
    ];

    /**
     * Company relationship
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    public function getMediaUrlsAttribute()
    {
        return $this->media->map(fn($media) => $media->getFullUrl());
    }
    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $media->getFullUrl(); // Ensure this is using your custom method
    }
    /**
     * Scopes
     */
    public function scopeForCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    protected static function newFactory(): EcoAppSettingFactory
    {
        return EcoAppSettingFactory::new();
    }
}
