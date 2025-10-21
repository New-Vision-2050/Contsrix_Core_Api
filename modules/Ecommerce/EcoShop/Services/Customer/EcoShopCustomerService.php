<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShop\Services\Customer;

use Modules\Ecommerce\EcoShop\Models\EcoShop;
use Modules\Ecommerce\EcoShop\Repositories\EcoShopRepository;
use Ramsey\Uuid\UuidInterface;

class EcoShopCustomerService
{
    public function __construct(
        private EcoShopRepository $repository,
    ) {
    }

    /**
     * Get public shop information
     */
    public function getPublicShopInfo(UuidInterface $companyId): EcoShop
    {
        return $this->repository->getByCompanyId($companyId);
    }

    /**
     * Get shop contact information
     */
    public function getContactInfo(UuidInterface $companyId): array
    {
        $shop = $this->repository->getByCompanyId($companyId);

        return [
            'email' => $shop->email,
            'phone' => $shop->phone,
            'whatsapp_number' => $shop->whatsapp_number,
            'website_url' => $shop->website_url,
        ];
    }

    /**
     * Get shop social media links
     */
    public function getSocialMediaLinks(UuidInterface $companyId): array
    {
        $shop = $this->repository->getByCompanyId($companyId);

        return [
            'facebook_url' => $shop->facebook_url,
            'instagram_url' => $shop->instagram_url,
            'twitter_url' => $shop->twitter_url,
            'tiktok_url' => $shop->tiktok_url,
            'snapchat_url' => $shop->snapchat_url,
        ];
    }

    /**
     * Get shop branding (logo, banner)
     */
    public function getBranding(UuidInterface $companyId): array
    {
        $shop = $this->repository->getByCompanyId($companyId);

        $logo = $shop->getFirstMedia('shop_logo');
        $banner = $shop->getFirstMedia('shop_banner');

        return [
            'logo' => $logo ? $logo->getFullUrl() : null,
            'banner' => $banner ? $banner->getFullUrl() : null,
            'shop_name' => $shop->name,
        ];
    }

    /**
     * Get basic shop info for header/footer
     */
    public function getBasicInfo(UuidInterface $companyId): array
    {
        $shop = $this->repository->getByCompanyId($companyId);
        $logo = $shop->getFirstMedia('shop_logo');

        return [
            'name' => $shop->name,
            'description' => $shop->description,
            'logo' => $logo ? $logo->getFullUrl() : null,
            'email' => $shop->email,
            'phone' => $shop->phone,
            'whatsapp_number' => $shop->whatsapp_number,
        ];
    }

    /**
     * Get shop SEO information
     */
    public function getSEOInfo(UuidInterface $companyId): array
    {
        $shop = $this->repository->getByCompanyId($companyId);

        return [
            'title' => $shop->name,
            'description' => $shop->description,
            'keywords' => $this->generateKeywords($shop),
            'og_image' => $shop->getFirstMedia('shop_banner')?->getFullUrl(),
        ];
    }

    /**
     * Check if shop is active and accessible
     */
    public function isShopActive(UuidInterface $companyId): bool
    {
        try {
            $shop = $this->repository->getByCompanyId($companyId);
            return $shop && $shop->is_active ?? true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get shop theme configuration
     */
    public function getThemeConfig(UuidInterface $companyId): array
    {
        $shop = $this->repository->getByCompanyId($companyId);

        return [
            'primary_color' => $shop->primary_color ?? '#007bff',
            'secondary_color' => $shop->secondary_color ?? '#6c757d',
            'font_family' => $shop->font_family ?? 'Arial, sans-serif',
            'layout_style' => $shop->layout_style ?? 'modern',
        ];
    }

    /**
     * Generate keywords for SEO
     */
    private function generateKeywords(EcoShop $shop): string
    {
        $keywords = [];
        
        if ($shop->name) {
            $keywords[] = $shop->name;
        }
        
        // Add common ecommerce keywords
        $keywords = array_merge($keywords, [
            'متجر إلكتروني',
            'تسوق أونلاين',
            'منتجات',
            'شراء',
            'توصيل'
        ]);

        return implode(', ', array_unique($keywords));
    }
}
