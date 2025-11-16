<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Presenters\Customer;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use Modules\Ecommerce\FlashDeal\Models\FlashDeal;
use Modules\Shared\Media\Presenters\MediaPresenter;

class EcoProductWithFlashDealPresenter extends AbstractPresenter
{
    private EcoProduct $ecoProduct;
    private ?FlashDeal $flashDeal;

    public function __construct(EcoProduct $ecoProduct, ?FlashDeal $flashDeal = null)
    {
        $this->ecoProduct = $ecoProduct;
        $this->flashDeal = $flashDeal ?? $ecoProduct->flashDeals->first();
    }

    protected function present(bool $isListing = false): array
    {
        $mainImage = $this->ecoProduct->getFirstMedia('eco_product_main_image');
        $originalPrice = (float) $this->ecoProduct->price;
        
        // FlashDeal doesn't have discount, so use product's own discount
        $finalPrice = (float) ($this->ecoProduct->final_price ?? $originalPrice);
        $discountAmount = $originalPrice - $finalPrice;
        $discountPercentage = $originalPrice > 0
            ? round((($originalPrice - $finalPrice) / $originalPrice) * 100, 0)
            : 0;
        $hasDiscount = (int) ($this->ecoProduct->has_active_discount ?? false);

        return [
            'id' => $this->ecoProduct->id,
            'name' => $this->ecoProduct->name,
            'price' => $originalPrice,
            'final_price' => round($finalPrice, 2),
            'discount_percentage' => $discountPercentage,
            'is_on_discount' => $hasDiscount,
            'is_featured' => (int) $this->ecoProduct->is_featured,
            'rating' => $this->ecoProduct->rating ?? 4.6,
            'reviews_count' => (int) ($this->ecoProduct->reviews_count ?? 0),
            'main_image' => $mainImage ? (new MediaPresenter($mainImage))->getData() : null,
            'deal' => $this->flashDeal ? [
                'id' => $this->flashDeal->id,
                'name' => $isListing 
                    ? $this->flashDeal->name 
                    : [
                        'ar' => $this->flashDeal->getTranslation('name', 'ar'),
                        'en' => $this->flashDeal->getTranslation('name', 'en'),
                    ],
                'start_date' => $this->flashDeal->start_date?->format('Y-m-d H:i:s'),
                'end_date' => $this->flashDeal->end_date?->format('Y-m-d H:i:s'),
            ] : null,
        ];
    }
}

