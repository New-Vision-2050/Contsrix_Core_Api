<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Presenters\Dashboard;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use Modules\Ecommerce\EcoBrand\Presenters\Dashboard\EcoBrandDashboardPresenter;
use Modules\Shared\Media\Presenters\MediaPresenter;
use Modules\Ecommerce\EcoCategory\Presenters\Dashboard\EcoCategoryDashboardPresenter;

class EcoProductDashboardDetailsPresenter extends AbstractPresenter
{
    private EcoProduct $ecoProduct;

    public function __construct(EcoProduct $ecoProduct)
    {
        $this->ecoProduct = $ecoProduct;
    }

    protected function present(bool $isListing = false): array
    {
        $firstMedia = $this->ecoProduct->getFirstMedia('eco_product_main_image');
        return [
            'id' => $this->ecoProduct->id,
            'company_id' => $this->ecoProduct->company_id,
            
            // Multilingual fields - using getTranslation method

            'name_ar' => $this->ecoProduct->getTranslation('name', 'ar'),
            'name_en' => $this->ecoProduct->getTranslation('name', 'en'),
            'description_ar' => $this->ecoProduct->getTranslation('description', 'ar'),
            'description_en' => $this->ecoProduct->getTranslation('description', 'en'),
            
            // Categories and Brand
            'category_id' => $this->ecoProduct->category_id,
            'sub_category_id' => $this->ecoProduct->sub_category_id,
            'sub_sub_category_id' => $this->ecoProduct->sub_sub_category_id,
            'brand_id' => $this->ecoProduct->brand_id,
            
            // Product specifications
            'type' => $this->ecoProduct->type,
            'unit' => $this->ecoProduct->unit,
            'sku' => $this->ecoProduct->sku,
            'warehouse_id' => $this->ecoProduct->warehouse_id,
            'gender' => $this->ecoProduct->gender,
            
            'countries' => $this->ecoProduct->countries->map(function ($country) {
                return [
                    'id' => $country->id,
                    'name' => $country->name,
                    'code' => $country->code ?? null,
                ];
            }),
            // Pricing and quantities
            'price' => (float) $this->ecoProduct->price,
            'min_order_quantity' => (int) $this->ecoProduct->min_order_quantity,
            'stock' => $this->ecoProduct->stock ? (int) $this->ecoProduct->stock : null,
            
            // Discount system
            'discount_type' => $this->ecoProduct->discount_type,
            'discount_value' => $this->ecoProduct->discount_value ? (float) $this->ecoProduct->discount_value : null,
            
            // Tax and shipping
            'vat_percentage' => $this->ecoProduct->vat_percentage ? (float) $this->ecoProduct->vat_percentage : null,
            'price_includes_vat' => (bool) $this->ecoProduct->price_includes_vat,
            'shipping_amount' => $this->ecoProduct->shipping_amount ? (float) $this->ecoProduct->shipping_amount : null,
            'shipping_included_in_price' => (bool) $this->ecoProduct->shipping_included_in_price,
            
            // Visibility
            'is_visible' => (int) $this->ecoProduct->is_visible,
            
            // Media
            'main_photo' => $this->ecoProduct->main_photo,
            'other_photos' => $this->ecoProduct->other_photos,
            
            // SEO (stored in main table)
            'meta_title' => $this->ecoProduct->meta_title,
            'meta_description' => $this->ecoProduct->meta_description,
            'meta_keywords' => $this->ecoProduct->meta_keywords,
            
            // Relationships
            'category' => $this->ecoProduct->category ? (new EcoCategoryDashboardPresenter($this->ecoProduct->category))->getData() : null,
            'sub_category' => $this->ecoProduct->subCategory ? (new EcoCategoryDashboardPresenter($this->ecoProduct->subCategory))->getData() : null,
            'sub_sub_category' => $this->ecoProduct->subSubCategory ? (new EcoCategoryDashboardPresenter($this->ecoProduct->subSubCategory))->getData() : null,
            'brand' => $this->ecoProduct->brand ? (new EcoBrandDashboardPresenter($this->ecoProduct->brand))->getData() : null,
            'warehouse' => $this->ecoProduct->warehouse ? [
                'id' => $this->ecoProduct->warehouse->id,
                'name' => $this->ecoProduct->warehouse->name,
            ] : null,

            
            // Media (using Spatie Media Library)
            'main_image' => $firstMedia ? (new MediaPresenter($firstMedia))->getData() : null,
            'other_images' => MediaPresenter::collection($this->ecoProduct->getMedia('eco_product_other_image')),
            
    
            

        ];
    }
    
    // /**
    //  * Calculate final price with discounts
    //  */
    // private function calculateFinalPrice(): float
    // {
    //     $basePrice = (float) $this->ecoProduct->price;
        
    //     if (!$this->ecoProduct->discount_type || !$this->ecoProduct->discount_value) {
    //         return $basePrice;
    //     }
        
    //     if ($this->ecoProduct->discount_type === 'percentage') {
    //         $discount = ($basePrice * $this->ecoProduct->discount_value) / 100;
    //     } else {
    //         $discount = (float) $this->ecoProduct->discount_value;
    //     }
        
    //     return max(0, $basePrice - $discount);
    // }
    
    // /**
    //  * Check if product has active discount
    //  */
    // private function hasActiveDiscount(): bool
    // {
    //     return $this->ecoProduct->discount_type && $this->ecoProduct->discount_value && $this->ecoProduct->discount_value > 0;
    // }
    
    // /**
    //  * Check if product is in stock
    //  */
    // private function isInStock(): bool
    // {
    //     // If stock is null, consider it as unlimited
    //     if ($this->ecoProduct->stock === null) {
    //         return true;
    //     }
        
    //     return $this->ecoProduct->stock > 0;
    // }
}
