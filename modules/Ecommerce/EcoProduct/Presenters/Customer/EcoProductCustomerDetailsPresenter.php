<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Presenters\Customer;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use Modules\Ecommerce\EcoBrand\Presenters\EcoBrandPresenter;
use Modules\Ecommerce\EcoCategory\Presenters\EcoCategoryPresenter;
use Modules\Shared\Media\Presenters\MediaPresenter;

class EcoProductCustomerDetailsPresenter extends AbstractPresenter
{
    private EcoProduct $ecoProduct;

    public function __construct(EcoProduct $ecoProduct)
    {
        $this->ecoProduct = $ecoProduct;
    }

    protected function present(bool $isListing = false): array
    {
        $mainImage = $this->ecoProduct->getFirstMedia('eco_product_main_image');
        $otherImages = $this->ecoProduct->getMedia('eco_product_other_images');
        $pricing = $this->calculatePricing();
        
        return [
            'id' => $this->ecoProduct->id,
            'name' => $this->ecoProduct->name,
            'description' => $this->ecoProduct->description,
            'price' => $pricing['original_price'],
            'discounted_price' => $pricing['discounted_price'],
            'discount_percentage' => $pricing['discount_percentage'],
            'has_discount' => $pricing['has_discount'],
            'sku' => $this->ecoProduct->sku,
            'stock' => $this->ecoProduct->unlimited_quantity ? 999 : $this->ecoProduct->stock,
            'unlimited_quantity' => (int)$this->ecoProduct->unlimited_quantity,
            'in_stock' => $this->ecoProduct->unlimited_quantity || $this->ecoProduct->stock > 0,
            'requires_shipping' => (int)$this->ecoProduct->requires_shipping,
            'is_taxable' => (int)$this->ecoProduct->is_taxable,
            'vat_percentage' => $this->ecoProduct->vat_percentage,
            
            // Media
            'main_image' => $mainImage ? (new MediaPresenter($mainImage))->getData() : null,
            'other_images' => $otherImages->map(fn($media) => (new MediaPresenter($media))->getData()),
            
            // Relationships
            'category' => $this->ecoProduct->category ? 
                (new EcoCategoryPresenter($this->ecoProduct->category))->getData() : null,
            'brand' => $this->ecoProduct->brand ? 
                (new EcoBrandPresenter($this->ecoProduct->brand))->getData() : null,
            
            // Customer-specific features
            'can_purchase' => $this->canPurchase(),
            'availability_status' => $this->getAvailabilityStatus(),
            'estimated_delivery' => $this->getEstimatedDelivery(),
            
            // SEO data for customer
            'seo' => $this->ecoProduct->seo ? [
                'meta_title' => $this->ecoProduct->seo->meta_title,
                'meta_description' => $this->ecoProduct->seo->meta_description,
                'meta_keywords' => $this->ecoProduct->seo->meta_keywords,
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

    private function canPurchase(): bool
    {
        return $this->ecoProduct->is_visible && 
               ($this->ecoProduct->unlimited_quantity || $this->ecoProduct->stock > 0);
    }

    private function getAvailabilityStatus(): string
    {
        if (!$this->ecoProduct->is_visible) {
            return 'unavailable';
        }

        if ($this->ecoProduct->unlimited_quantity) {
            return 'in_stock';
        }

        if ($this->ecoProduct->stock > 10) {
            return 'in_stock';
        } elseif ($this->ecoProduct->stock > 0) {
            return 'low_stock';
        } else {
            return 'out_of_stock';
        }
    }

    private function getEstimatedDelivery(): ?string
    {
        if (!$this->ecoProduct->requires_shipping) {
            return null;
        }

        // This could be calculated based on warehouse location, shipping method, etc.
        return '3-5 business days';
    }
}
