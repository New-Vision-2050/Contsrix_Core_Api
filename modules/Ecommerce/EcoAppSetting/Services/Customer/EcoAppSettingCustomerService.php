<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Services\Customer;

use Modules\Ecommerce\EcoAppSetting\Models\EcoAppSetting;
use Modules\Ecommerce\EcoAppSetting\Repositories\EcoAppSettingRepository;
use Ramsey\Uuid\UuidInterface;

class EcoAppSettingCustomerService
{
    public function __construct(
        private EcoAppSettingRepository $repository,
    ) {
    }

    /**
     * Get public app settings for customer app
     */
    public function getPublicAppSettings(UuidInterface $companyId): EcoAppSetting
    {
        $settings = $this->repository->findByCompanyId($companyId);
        
        if (!$settings) {
            // Return default settings if none exist
            $settings = $this->createDefaultSettings($companyId);
        }
        
        return $settings;
    }

    /**
     * Get theme settings
     */
    public function getThemeSettings(UuidInterface $companyId): array
    {
        $settings = $this->getPublicAppSettings($companyId);
        
        return [
            'background_color' => $settings->background_color ?? '#FFFFFF',
            'enable_search' => (bool) $settings->enable_search,
            'show_logo_on_first_page' => (bool) $settings->show_logo_on_first_page,
            'show_logo_on_front_page' => (bool) $settings->show_logo_on_front_page,
        ];
    }

    /**
     * Get product display settings
     */
    public function getProductDisplaySettings(UuidInterface $companyId): array
    {
        $settings = $this->getPublicAppSettings($companyId);
        
        return [
            'product_display_category' => (bool) $settings->product_display_category,
            'product_display_type' => $settings->product_display_type ?? 'grid',
            'product_columns_count' => (int) ($settings->product_columns_count ?? 2),
            'product_rows_count' => (int) ($settings->product_rows_count ?? 3),
            'show_products_in_app' => (bool) $settings->show_products_in_app,
            'count_photos' => (int) ($settings->count_photos ?? 5),
            
            // Product details
            'show_product_image' => (bool) $settings->show_product_image,
            'show_product_rating' => (bool) $settings->show_product_rating,
            'show_similar_products' => (bool) $settings->show_similar_products,
            'show_product_price' => (bool) $settings->show_product_price,
            'show_product_shipping' => (bool) $settings->show_product_shipping,
            'show_product_description' => (bool) $settings->show_product_description,
            'show_product_color_code' => (bool) $settings->show_product_color_code,
            'show_product_size' => (bool) $settings->show_product_size,
            'show_product_comment' => (bool) $settings->show_product_comment,
            'can_product_comment' => (bool) $settings->can_product_comment,
        ];
    }

    /**
     * Get cart settings
     */
    public function getCartSettings(UuidInterface $companyId): array
    {
        $settings = $this->getPublicAppSettings($companyId);
        
        return [
            'show_cart_products' => (bool) $settings->show_cart_products,
            'cart_display_type' => $settings->cart_display_type ?? 'list',
            'cart_columns_count' => (int) ($settings->cart_columns_count ?? 1),
            'show_cart_in_app' => (bool) $settings->show_cart_in_app,
        ];
    }

    /**
     * Get filter settings
     */
    public function getFilterSettings(UuidInterface $companyId): array
    {
        $settings = $this->getPublicAppSettings($companyId);
        
        return [
            'show_filter_in_app' => (bool) $settings->show_filter_in_app,
            'show_category_filter' => (bool) $settings->show_category_filter,
            'show_product_filter' => (bool) $settings->show_product_filter,
            'show_color_filter' => (bool) $settings->show_color_filter,
            'show_brand_filter' => (bool) $settings->show_brand_filter,
            'show_size_filter' => (bool) $settings->show_size_filter,
            'show_price_filter' => (bool) $settings->show_price_filter,
            'show_rating_filter' => (bool) $settings->show_rating_filter,
            'show_discount_filter' => (bool) $settings->show_discount_filter,
        ];
    }

    /**
     * Get favorites settings
     */
    public function getFavoritesSettings(UuidInterface $companyId): array
    {
        $settings = $this->getPublicAppSettings($companyId);
        
        return [
            'show_favorites_search' => (bool) $settings->show_favorites_search,
            'show_favorites_delete' => (bool) $settings->show_favorites_delete,
            'show_favorites_products' => (bool) $settings->show_favorites_products,
            'favorites_display_type' => $settings->favorites_display_type ?? 'grid',
            'show_favorites_in_app' => (bool) $settings->show_favorites_in_app,
        ];
    }

    /**
     * Get product card settings
     */
    public function getProductCardSettings(UuidInterface $companyId): array
    {
        $settings = $this->getPublicAppSettings($companyId);
        
        return [
            'show_product_name' => (bool) $settings->show_product_name,
            'show_product_description_card' => (bool) $settings->show_product_description_card,
            'show_product_price_card' => (bool) $settings->show_product_price_card,
            'show_product_color' => (bool) $settings->show_product_color,
            'show_product_size_card' => (bool) $settings->show_product_size_card,
            'show_similar_products_card' => (bool) $settings->show_similar_products_card,
            'product_card_display_type' => $settings->product_card_display_type ?? 'card',
            'product_card_columns_count' => (int) ($settings->product_card_columns_count ?? 1),
            'show_discount_code' => (bool) $settings->show_discount_code,
            'show_payment_details' => (bool) $settings->show_payment_details,
            'show_product_card_in_app' => (bool) $settings->show_product_card_in_app,
        ];
    }

    /**
     * Get complete app configuration for mobile app initialization
     */
    public function getAppConfig(UuidInterface $companyId): array
    {
        $settings = $this->getPublicAppSettings($companyId);
        
        return [
            'theme' => $this->getThemeSettings($companyId),
            'product_display' => $this->getProductDisplaySettings($companyId),
            'cart' => $this->getCartSettings($companyId),
            'filters' => $this->getFilterSettings($companyId),
            'favorites' => $this->getFavoritesSettings($companyId),
            'product_card' => $this->getProductCardSettings($companyId),
            'policies' => [
                'show_terms_text' => (bool) $settings->show_terms_text,
                'show_privacy_policy' => (bool) $settings->show_privacy_policy,
                'show_return_policy' => (bool) $settings->show_return_policy,
            ],
        ];
    }

    /**
     * Check if app is configured for the company
     */
    public function isAppConfigured(UuidInterface $companyId): bool
    {
        $settings = $this->repository->findByCompanyId($companyId);
        return $settings !== null;
    }

    /**
     * Get app version and compatibility info
     */
    public function getAppVersionInfo(UuidInterface $companyId): array
    {
        return [
            'min_app_version' => '1.0.0',
            'current_api_version' => '1.0',
            'force_update' => false,
            'maintenance_mode' => false,
        ];
    }

    /**
     * Create default settings (private method)
     */
    private function createDefaultSettings(UuidInterface $companyId): EcoAppSetting
    {
        // This would typically be handled by the dashboard service
        // For now, return a new instance with default values
        $setting = new EcoAppSetting();
        $setting->company_id = $companyId->toString();
        $setting->fill($this->getDefaultValues());
        
        return $setting;
    }

    /**
     * Get default values
     */
    private function getDefaultValues(): array
    {
        return [
            'background_color' => '#FFFFFF',
            'enable_search' => 1,
            'show_logo_on_first_page' => 1,
            'show_logo_on_front_page' => 1,
            'count_photos' => 5,
            'product_display_category' => 1,
            'product_display_type' => 'grid',
            'product_columns_count' => 2,
            'product_rows_count' => 3,
            'show_products_in_app' => 1,
            'show_favorites_search' => 1,
            'show_favorites_delete' => 1,
            'show_favorites_products' => 1,
            'favorites_display_type' => 'grid',
            'show_favorites_in_app' => 1,
            'show_product_image' => 1,
            'show_product_rating' => 1,
            'show_similar_products' => 1,
            'show_product_price' => 1,
            'show_product_shipping' => 1,
            'show_product_description' => 1,
            'show_product_color_code' => 1,
            'show_product_size' => 1,
            'show_product_comment' => 1,
            'can_product_comment' => 1,
            'show_cart_products' => 1,
            'cart_display_type' => 'list',
            'cart_columns_count' => 1,
            'show_cart_in_app' => 1,
            'show_product_name' => 1,
            'show_product_description_card' => 1,
            'show_product_price_card' => 1,
            'show_product_color' => 1,
            'show_product_size_card' => 1,
            'show_similar_products_card' => 1,
            'product_card_display_type' => 'card',
            'product_card_columns_count' => 1,
            'show_discount_code' => 1,
            'show_payment_details' => 1,
            'show_product_card_in_app' => 1,
            'show_filter_in_app' => 1,
            'show_category_filter' => 1,
            'show_product_filter' => 1,
            'show_color_filter' => 1,
            'show_brand_filter' => 1,
            'show_size_filter' => 1,
            'show_price_filter' => 1,
            'show_rating_filter' => 1,
            'show_discount_filter' => 1,
            'show_terms_text' => 1,
            'show_privacy_policy' => 1,
            'show_return_policy' => 1,
        ];
    }
}
