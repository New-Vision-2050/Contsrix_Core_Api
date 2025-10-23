<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Presenters\Customer;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\EcoAppSetting\Models\EcoAppSetting;

class EcoAppSettingCustomerPresenter extends AbstractPresenter
{
    private EcoAppSetting $ecoAppSetting;

    public function __construct(EcoAppSetting $ecoAppSetting)
    {
        $this->ecoAppSetting = $ecoAppSetting;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            // Theme & UI Settings (Customer relevant)
            'theme' => [
                'background_color' => $this->ecoAppSetting->background_color ?? '#FFFFFF',
                'enable_search' => (bool) $this->ecoAppSetting->enable_search,
                'show_logo_on_first_page' => (bool) $this->ecoAppSetting->show_logo_on_first_page,
                'show_logo_on_front_page' => (bool) $this->ecoAppSetting->show_logo_on_front_page,
                'count_photos' => (int) ($this->ecoAppSetting->count_photos ?? 5),
            ],
            
            // Product Display Settings
            'product_display' => [
                'product_display_category' => (bool) $this->ecoAppSetting->product_display_category,
                'product_display_type' => $this->ecoAppSetting->product_display_type ?? 'grid',
                'product_columns_count' => (int) ($this->ecoAppSetting->product_columns_count ?? 2),
                'product_rows_count' => (int) ($this->ecoAppSetting->product_rows_count ?? 3),
                'show_products_in_app' => (bool) $this->ecoAppSetting->show_products_in_app,
                
                // Product details visibility
                'show_product_image' => (bool) $this->ecoAppSetting->show_product_image,
                'show_product_rating' => (bool) $this->ecoAppSetting->show_product_rating,
                'show_similar_products' => (bool) $this->ecoAppSetting->show_similar_products,
                'show_product_price' => (bool) $this->ecoAppSetting->show_product_price,
                'show_product_shipping' => (bool) $this->ecoAppSetting->show_product_shipping,
                'show_product_description' => (bool) $this->ecoAppSetting->show_product_description,
                'show_product_color_code' => (bool) $this->ecoAppSetting->show_product_color_code,
                'show_product_size' => (bool) $this->ecoAppSetting->show_product_size,
                'show_product_comment' => (bool) $this->ecoAppSetting->show_product_comment,
                'can_product_comment' => (bool) $this->ecoAppSetting->can_product_comment,
            ],
            
            // Favorites Settings
            'favorites' => [
                'show_favorites_search' => (bool) $this->ecoAppSetting->show_favorites_search,
                'show_favorites_delete' => (bool) $this->ecoAppSetting->show_favorites_delete,
                'show_favorites_products' => (bool) $this->ecoAppSetting->show_favorites_products,
                'favorites_display_type' => $this->ecoAppSetting->favorites_display_type ?? 'grid',
                'show_favorites_in_app' => (bool) $this->ecoAppSetting->show_favorites_in_app,
            ],
            
            // Cart Settings
            'cart' => [
                'show_cart_products' => (bool) $this->ecoAppSetting->show_cart_products,
                'cart_display_type' => $this->ecoAppSetting->cart_display_type ?? 'list',
                'cart_columns_count' => (int) ($this->ecoAppSetting->cart_columns_count ?? 1),
                'show_cart_in_app' => (bool) $this->ecoAppSetting->show_cart_in_app,
            ],
            
            // Product Card Settings
            'product_card' => [
                'show_product_name' => (bool) $this->ecoAppSetting->show_product_name,
                'show_product_description_card' => (bool) $this->ecoAppSetting->show_product_description_card,
                'show_product_price_card' => (bool) $this->ecoAppSetting->show_product_price_card,
                'show_product_color' => (bool) $this->ecoAppSetting->show_product_color,
                'show_product_size_card' => (bool) $this->ecoAppSetting->show_product_size_card,
                'show_similar_products_card' => (bool) $this->ecoAppSetting->show_similar_products_card,
                'product_card_display_type' => $this->ecoAppSetting->product_card_display_type ?? 'card',
                'product_card_columns_count' => (int) ($this->ecoAppSetting->product_card_columns_count ?? 1),
                'show_discount_code' => (bool) $this->ecoAppSetting->show_discount_code,
                'show_payment_details' => (bool) $this->ecoAppSetting->show_payment_details,
                'show_product_card_in_app' => (bool) $this->ecoAppSetting->show_product_card_in_app,
            ],
            
            // Filter Settings
            'filters' => [
                'show_filter_in_app' => (bool) $this->ecoAppSetting->show_filter_in_app,
                'show_category_filter' => (bool) $this->ecoAppSetting->show_category_filter,
                'show_product_filter' => (bool) $this->ecoAppSetting->show_product_filter,
                'show_color_filter' => (bool) $this->ecoAppSetting->show_color_filter,
                'show_brand_filter' => (bool) $this->ecoAppSetting->show_brand_filter,
                'show_size_filter' => (bool) $this->ecoAppSetting->show_size_filter,
                'show_price_filter' => (bool) $this->ecoAppSetting->show_price_filter,
                'show_rating_filter' => (bool) $this->ecoAppSetting->show_rating_filter,
                'show_discount_filter' => (bool) $this->ecoAppSetting->show_discount_filter,
            ],
            
            // Policy Settings (Customer relevant)
            'policies' => [
                'show_terms_text' => (bool) $this->ecoAppSetting->show_terms_text,
                'show_privacy_policy' => (bool) $this->ecoAppSetting->show_privacy_policy,
                'show_return_policy' => (bool) $this->ecoAppSetting->show_return_policy,
            ],
            
            // App Configuration
            'app_config' => [
                'version' => '1.0.0',
                'force_update' => false,
                'maintenance_mode' => false,
            ],
        ];
    }
}
