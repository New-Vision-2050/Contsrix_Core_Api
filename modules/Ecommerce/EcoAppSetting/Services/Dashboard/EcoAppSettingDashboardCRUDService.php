<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Services\Dashboard;

use Modules\Ecommerce\EcoAppSetting\DTO\Dashboard\CreateEcoAppSettingDashboardDTO;
use Modules\Ecommerce\EcoAppSetting\Commands\Dashboard\UpdateEcoAppSettingDashboardCommand;
use Modules\Ecommerce\EcoAppSetting\Models\EcoAppSetting;
use Modules\Ecommerce\EcoAppSetting\Repositories\EcoAppSettingRepository;
use Ramsey\Uuid\UuidInterface;

class EcoAppSettingDashboardCRUDService
{
    public function __construct(
        private EcoAppSettingRepository $repository,
    ) {
    }

    /**
     * Get paginated app settings list
     */
    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    /**
     * Get app setting by ID
     */
    public function get(UuidInterface $id): EcoAppSetting
    {
        return $this->repository->getEcoAppSetting($id);
    }

    /**
     * Create new app setting
     */
    public function create(CreateEcoAppSettingDashboardDTO $createEcoAppSettingDTO): EcoAppSetting
    {
        return $this->repository->createEcoAppSetting($createEcoAppSettingDTO->toArray());
    }

    /**
     * Get company app settings (main settings for the company)
     */
    public function getCompanySettings(UuidInterface $companyId): EcoAppSetting
    {
        $settings = $this->repository->findByCompanyId($companyId);
        
        if (!$settings) {
            // Create default settings if none exist
            $settings = $this->createDefaultSettings($companyId);
        }
        
        return $settings;
    }

    /**
     * Update company app settings
     */
    public function updateCompanySettings(UuidInterface $companyId, UpdateEcoAppSettingDashboardCommand $command): EcoAppSetting
    {
        $settings = $this->getCompanySettings($companyId);
        
        $this->repository->updateEcoAppSetting($settings->id, $command->toArray());
        
        return $this->repository->getEcoAppSetting($settings->id);
    }

    /**
     * Reset app settings to default values
     */
    public function resetToDefault(UuidInterface $companyId): EcoAppSetting
    {
        $settings = $this->getCompanySettings($companyId);
        
        $defaultData = $this->getDefaultSettingsData($companyId);
        $this->repository->updateEcoAppSetting($settings->id, $defaultData);
        
        return $this->repository->getEcoAppSetting($settings->id);
    }

    /**
     * Get app settings statistics
     */
    public function getStatistics(): array
    {
        $companyId = tenant('id');
        
        return [
            'total_settings' => $this->repository->countByCompanyId($companyId),
            'theme_settings_configured' => $this->repository->countThemeSettingsConfigured($companyId),
            'product_display_configured' => $this->repository->countProductDisplayConfigured($companyId),
            'filter_settings_configured' => $this->repository->countFilterSettingsConfigured($companyId),
            'last_updated' => $this->repository->getLastUpdated($companyId),
        ];
    }

    /**
     * Create default settings for a company
     */
    private function createDefaultSettings(UuidInterface $companyId): EcoAppSetting
    {
        $defaultData = $this->getDefaultSettingsData($companyId);
        
        return $this->repository->createEcoAppSetting($defaultData);
    }

    /**
     * Get default settings data
     */
    private function getDefaultSettingsData(UuidInterface $companyId): array
    {
        return [
            'company_id' => $companyId->toString(),
            
            // Theme & UI Settings
            'background_color' => '#FFFFFF',
            'enable_search' => 1,
            
            // First page settings
            'show_logo_on_first_page' => 1,
            
            // Front page settings
            'show_logo_on_front_page' => 1,
            'count_photos' => 5,
            
            // Display products
            'product_display_category' => 1,
            'product_display_type' => 'grid',
            'product_columns_count' => 2,
            'product_rows_count' => 3,
            'show_products_in_app' => 1,
            
            // Display favorites
            'show_favorites_search' => 1,
            'show_favorites_delete' => 1,
            'show_favorites_products' => 1,
            'favorites_display_type' => 'grid',
            'show_favorites_in_app' => 1,
            
            // Product selection settings
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
            
            // Cart settings
            'show_cart_products' => 1,
            'cart_display_type' => 'list',
            'cart_columns_count' => 1,
            'show_cart_in_app' => 1,
            
            // Product card settings
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
            
            // Filter settings
            'show_filter_in_app' => 1,
            'show_category_filter' => 1,
            'show_product_filter' => 1,
            'show_color_filter' => 1,
            'show_brand_filter' => 1,
            'show_size_filter' => 1,
            'show_price_filter' => 1,
            'show_rating_filter' => 1,
            'show_discount_filter' => 1,
            
            // Terms and Conditions settings
            'show_terms_text' => 1,
            'show_privacy_policy' => 1,
            'show_return_policy' => 1,
        ];
    }
}
