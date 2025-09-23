<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShop\Presenters\Customer;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\EcoShop\Models\EcoShop;
use Modules\Shared\Media\Presenters\MediaPresenter;

class EcoShopCustomerPresenter extends AbstractPresenter
{
    private EcoShop $ecoShop;

    public function __construct(EcoShop $ecoShop)
    {
        $this->ecoShop = $ecoShop;
    }

    protected function present(bool $isListing = false): array
    {
        $logo = $this->ecoShop->getFirstMedia('shop_logo');
        $banner = $this->ecoShop->getFirstMedia('shop_banner');
        
        return [
            'name' => $this->ecoShop->name,
            'description' => $this->ecoShop->description,
            
            // Contact Information
            'contact' => [
                'email' => $this->ecoShop->email,
                'phone' => $this->ecoShop->phone,
                'whatsapp_number' => $this->ecoShop->whatsapp_number,
                'website_url' => $this->ecoShop->website_url,
            ],
            
            // Social Media Links (public)
            'social_media' => [
                'facebook_url' => $this->ecoShop->facebook_url,
                'instagram_url' => $this->ecoShop->instagram_url,
                'twitter_url' => $this->ecoShop->twitter_url,
                'tiktok_url' => $this->ecoShop->tiktok_url,
                'snapchat_url' => $this->ecoShop->snapchat_url,
            ],
            
            // Branding
            'branding' => [
                'logo_url' => $logo ? $logo->getFullUrl() : null,
                'banner_url' => $banner ? $banner->getFullUrl() : null,
            ],
            
            // Theme Configuration for customer frontend
            'theme' => [
                'primary_color' => $this->ecoShop->primary_color ?? '#007bff',
                'secondary_color' => $this->ecoShop->secondary_color ?? '#6c757d',
                'font_family' => $this->ecoShop->font_family ?? 'Arial, sans-serif',
                'layout_style' => $this->ecoShop->layout_style ?? 'modern',
            ],
            
            // SEO Information
            'seo' => [
                'title' => $this->ecoShop->name,
                'description' => $this->ecoShop->description,
                'keywords' => $this->generateKeywords(),
                'og_image' => $banner ? $banner->getFullUrl() : ($logo ? $logo->getFullUrl() : null),
            ],
        ];
    }

    private function generateKeywords(): string
    {
        $keywords = [];
        
        if ($this->ecoShop->name) {
            $keywords[] = $this->ecoShop->name;
        }
        
        // Add common ecommerce keywords in Arabic
        $keywords = array_merge($keywords, [
            'متجر إلكتروني',
            'تسوق أونلاين',
            'منتجات',
            'شراء',
            'توصيل',
            'تسوق',
            'متجر'
        ]);

        return implode(', ', array_unique($keywords));
    }
}
