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
            'discounted_price' => $pricing['discounted_price'],
            'discount_percentage' => $pricing['discount_percentage'],
            'has_discount' => $pricing['has_discount'],
            'sku' => $this->ecoProduct->sku,
            'stock' => $this->ecoProduct->unlimited_quantity ? 999 : $this->ecoProduct->stock,
            'unlimited_quantity' => (int)$this->ecoProduct->unlimited_quantity,
            'in_stock' => $this->ecoProduct->unlimited_quantity || $this->ecoProduct->stock > 0,
            'requires_shipping' => (int)$this->ecoProduct->requires_shipping,
            'main_image' => $firstMedia ? (new MediaPresenter($firstMedia))->getData() : null,
            'category' => $this->ecoProduct->category ? 
                (new EcoCategoryPresenter($this->ecoProduct->category))->getData() : null,
            'brand' => $this->ecoProduct->brand ? 
                (new EcoBrandDashboardPresenter($this->ecoProduct->brand))->getData() : null,
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
