<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Presenters\Customer;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use Modules\Ecommerce\FeatureDeal\Models\FeatureDeal;
use Modules\Shared\Media\Presenters\MediaPresenter;

class EcoProductWithFeatureDealPresenter extends AbstractPresenter
{
    private EcoProduct $ecoProduct;
    private ?FeatureDeal $featureDeal;

    public function __construct(EcoProduct $ecoProduct, ?FeatureDeal $featureDeal = null)
    {
        $this->ecoProduct = $ecoProduct;
        $this->featureDeal = $featureDeal ?? $ecoProduct->featureDeals->first();
    }

    protected function present(bool $isListing = false): array
    {
        $mainImage = $this->ecoProduct->getFirstMedia('eco_product_main_image');
        $originalPrice = (float) $this->ecoProduct->price;

        // Calculate discount from FeatureDeal if available
        $discountAmount = 0.0;
        $discountPercentage = 0;
        $hasDiscount = 0;

        if ($this->featureDeal && $this->featureDeal->discount_type && $this->featureDeal->discount_value) {
            if ($this->featureDeal->discount_type === 'percentage') {
                $discountAmount = ($originalPrice * (float) $this->featureDeal->discount_value) / 100;
            } else {
                $discountAmount = (float) $this->featureDeal->discount_value;
            }

            $finalPrice = max(0, $originalPrice - $discountAmount);
            $discountPercentage = $originalPrice > 0
                ? round(($discountAmount / $originalPrice) * 100, 0)
                : 0;
            $hasDiscount = $discountAmount > 0 ? 1 : 0;
        } else {
            // Fallback to product's own discount
            $finalPrice = (float) ($this->ecoProduct->final_price ?? $originalPrice);
            $discountAmount = $originalPrice - $finalPrice;
            $discountPercentage = $originalPrice > 0
                ? round((($originalPrice - $finalPrice) / $originalPrice) * 100, 0)
                : 0;
            $hasDiscount = (int) ($this->ecoProduct->has_active_discount ?? false);
        }

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
            'deal' => $this->featureDeal ? [
                'id' => $this->featureDeal->id,
                'name' => $isListing 
                    ? $this->featureDeal->name 
                    : [
                        'ar' => $this->featureDeal->getTranslation('name', 'ar'),
                        'en' => $this->featureDeal->getTranslation('name', 'en'),
                    ],
                'discount_type' => $this->featureDeal->discount_type,
                'discount_value' => (float) $this->featureDeal->discount_value,
                'start_date' => $this->featureDeal->start_date?->toDateString(),
                'end_date' => $this->featureDeal->end_date?->toDateString(),
            ] : null,
        ];
    }
}

