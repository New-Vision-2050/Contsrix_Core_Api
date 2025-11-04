<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Presenters\Customer;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use Modules\Ecommerce\EcoBrand\Presenters\Dashboard\EcoBrandDashboardPresenter;
use Modules\Ecommerce\EcoCategory\Presenters\EcoCategoryPresenter;
use Modules\Shared\Media\Presenters\MediaPresenter;

class EcoProductCustomerPresenter extends AbstractPresenter
{
    private EcoProduct $ecoProduct;

    public function __construct(EcoProduct $ecoProduct)
    {
        $this->ecoProduct = $ecoProduct;
    }

    protected function present(bool $isListing = false): array
    {
        $firstMedia = $this->ecoProduct->getFirstMedia('eco_product_main_image');
        $pricing = $this->calculatePricing();
        
        return [
            'id' => $this->ecoProduct->id,
            'name' => $this->ecoProduct->name,
            'price' => $pricing['original_price'],
            'final_price' => $pricing['discounted_price'],
            'discount_percentage' => round($pricing['discount_percentage'], 0),
            'is_on_discount' => $pricing['has_discount'],
            'rating' => $this->ecoProduct->rating ?? 4.6,
            'reviews_count' => $this->ecoProduct->reviews_count ?? 0,
            'main_image' => $firstMedia ? [
                'url' => $firstMedia->getUrl(),
                'thumb' => $firstMedia->getUrl('thumb')
            ] : null,
        ];
    }

    private function calculatePricing(): array
    {
        $originalPrice = $this->ecoProduct->price;
        $discountedPrice = $originalPrice;
        $hasDiscount = false;

        if ($this->ecoProduct->has_discount && $this->isDiscountActive()) {
            $hasDiscount = true;
            
            if ($this->ecoProduct->discount_percentage) {
                $discountAmount = ($originalPrice * $this->ecoProduct->discount_percentage) / 100;
                if ($this->ecoProduct->max_discount_amount) {
                    $discountAmount = min($discountAmount, $this->ecoProduct->max_discount_amount);
                }
                $discountedPrice = $originalPrice - $discountAmount;
            } elseif ($this->ecoProduct->discount_amount) {
                $discountedPrice = max(0, $originalPrice - $this->ecoProduct->discount_amount);
            }
        }

        return [
            'original_price' => $originalPrice,
            'discounted_price' => $discountedPrice,
            'discount_percentage' => $hasDiscount ? (($originalPrice - $discountedPrice) / $originalPrice) * 100 : 0,
            'has_discount' => $hasDiscount,
        ];
    }

    private function isDiscountActive(): bool
    {
        $now = now();
        
        if ($this->ecoProduct->discount_start_date && $now->lt($this->ecoProduct->discount_start_date)) {
            return false;
        }
        
        if ($this->ecoProduct->discount_end_date && $now->gt($this->ecoProduct->discount_end_date)) {
            return false;
        }
        
        return true;
    }
}
