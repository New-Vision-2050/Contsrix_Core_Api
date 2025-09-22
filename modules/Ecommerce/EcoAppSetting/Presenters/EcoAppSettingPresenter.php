<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Presenters;

use Modules\Ecommerce\EcoAppSetting\Models\EcoAppSetting;
use Modules\Shared\Media\Presenters\MediaPresenter;

class EcoAppSettingPresenter
{
    private EcoAppSetting $ecoAppSetting;

    public function __construct(EcoAppSetting $ecoAppSetting)
    {
        $this->ecoAppSetting = $ecoAppSetting;
    }

    public function getData(): array
    {
        $emptyCartImage = $this->ecoAppSetting->getFirstMedia('empty_cart_image');
        return [
            'id' => $this->ecoAppSetting->id,
            'company_id' => $this->ecoAppSetting->company_id,
            
            // Theme Settings
            'background_color' => $this->ecoAppSetting->background_color,
            'enable_search' => (int) $this->ecoAppSetting->enable_search,

            // Front Page Settings
            'show_logo_on_first_page' => (int) $this->ecoAppSetting->show_logo_on_first_page,
            'show_logo_on_front_page' => (int) $this->ecoAppSetting->show_logo_on_front_page,
            'count_photos' => $this->ecoAppSetting->count_photos,
            'logo' => MediaPresenter::collection( $this->ecoAppSetting->getMedia("eco_logo")),
            
            // Product Display Settings
            'product_display_category' => $this->ecoAppSetting->product_display_category,
            'product_display_type' => $this->ecoAppSetting->product_display_type,
            'product_columns_count' => $this->ecoAppSetting->product_columns_count,
            'product_rows_count' => $this->ecoAppSetting->product_rows_count,
            'show_products_in_app' => (int) $this->ecoAppSetting->show_products_in_app,
            
            // Favorites Settings
            'show_favorites_search' => (int) $this->ecoAppSetting->show_favorites_search,
            'show_favorites_delete' => (int) $this->ecoAppSetting->show_favorites_delete,
            'show_favorites_products' => (int) $this->ecoAppSetting->show_favorites_products,
            'favorites_display_type' => $this->ecoAppSetting->favorites_display_type,
            'show_favorites_in_app' => (int) $this->ecoAppSetting->show_favorites_in_app,
            
            // Product Card Settings
            'show_product_name' => (int) $this->ecoAppSetting->show_product_name,
            'show_product_description_card' => (int) $this->ecoAppSetting->show_product_description_card,
            'show_product_price_card' => (int) $this->ecoAppSetting->show_product_price_card,
            'show_product_color' => (int) $this->ecoAppSetting->show_product_color,
            'show_product_size_card' => (int) $this->ecoAppSetting->show_product_size_card,
            'show_similar_products_card' => (int) $this->ecoAppSetting->show_similar_products_card,
            'product_card_display_type' => $this->ecoAppSetting->product_card_display_type,
            'product_card_columns_count' => $this->ecoAppSetting->product_card_columns_count,
            'show_discount_code' => (int) $this->ecoAppSetting->show_discount_code,
            'show_payment_details' => (int) $this->ecoAppSetting->show_payment_details,
            'show_product_card_in_app' => (int) $this->ecoAppSetting->show_product_card_in_app,
            
            // Filter Display Settings
            'show_filter_in_app' => (int) $this->ecoAppSetting->show_filter_in_app,
            'show_category_filter' => (int) $this->ecoAppSetting->show_category_filter,
            'show_product_filter' => (int) $this->ecoAppSetting->show_product_filter,
            'show_color_filter' => (int) $this->ecoAppSetting->show_color_filter,
            'show_brand_filter' => (int) $this->ecoAppSetting->show_brand_filter,
            'show_size_filter' => (int) $this->ecoAppSetting->show_size_filter,
            'show_price_filter' => (int) $this->ecoAppSetting->show_price_filter,
            'show_rating_filter' => (int) $this->ecoAppSetting->show_rating_filter,
            'show_discount_filter' => (int) $this->ecoAppSetting->show_discount_filter,
            
            // Terms and Conditions Settings
            'show_terms_text' => (int) $this->ecoAppSetting->show_terms_text,
            'show_privacy_policy' => (int) $this->ecoAppSetting->show_privacy_policy,
            'show_return_policy' => (int) $this->ecoAppSetting->show_return_policy,
            
            // Cart Settings
            'show_cart_products' => (int) $this->ecoAppSetting->show_cart_products,
            'cart_display_type' => $this->ecoAppSetting->cart_display_type,
            'cart_columns_count' => $this->ecoAppSetting->cart_columns_count,
            'show_cart_in_app' => (int) $this->ecoAppSetting->show_cart_in_app,
            'empty_cart_image' => $emptyCartImage ? (new MediaPresenter($emptyCartImage))->getData() : null,
        ];
    }

    public static function collection($items): array
    {
        return array_map(function ($item) {
            return (new self($item))->getData();
        }, $items);
    }
}
