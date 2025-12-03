<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Presenters\Website;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use Modules\Shared\Media\Presenters\MediaPresenter;

class EcoProductWebsiteDetailsPresenter extends AbstractPresenter
{
    private EcoProduct $ecoProduct;

    public function __construct(EcoProduct $ecoProduct)
    {
        $this->ecoProduct = $ecoProduct;
    }

    protected function present(bool $isListing = false): array
    {
        $mainImage = $this->ecoProduct->getFirstMedia('eco_product_main_image');
        $otherImages = $this->ecoProduct->getMedia('eco_product_other_image');
        $originalPrice = (float) $this->ecoProduct->price;
        $finalPrice = (float) ($this->ecoProduct->final_price ?? $originalPrice);
        $hasDiscount = (int) ($this->ecoProduct->has_active_discount ?? false);
        $discountPercentage = $originalPrice > 0
            ? round((($originalPrice - $finalPrice) / $originalPrice) * 100, 0)
            : 0;

        $data = [
            'id' => $this->ecoProduct->id,
            'name' => $this->ecoProduct->name,
            'description' => $this->ecoProduct->description,
            'price' => $originalPrice,
            'final_price' => $finalPrice,
            'discount_percentage' => $discountPercentage,
            'is_on_discount' => $hasDiscount,
            'is_featured' => (int) $this->ecoProduct->is_featured,
            'sku' => $this->ecoProduct->sku,
            'stock' => $this->ecoProduct->stock ?? 0,
            'min_order_quantity' => $this->ecoProduct->min_order_quantity ?? 1,
            'in_stock' => (int) ($this->ecoProduct->is_in_stock ?? ($this->ecoProduct->stock > 0)),
            'rating' => $this->ecoProduct->rating ?? 4.6,
            'reviews_count' => (int) $this->ecoProduct->reviews_count ?? 0,
            'type' => $this->ecoProduct->type ?? 'normal',
            'gender' => $this->ecoProduct->gender ?? 'all',
            'unit' => $this->ecoProduct->unit ?? 'piece',
            'video_url' => $this->ecoProduct->video_url,
            
            // Media
            'main_image' => $mainImage ? (new MediaPresenter($mainImage))->getData() : null,
            'other_images' => $otherImages->map(fn($media) => (new MediaPresenter($media))->getData())->values()->all(),
        ];

        // Only include category if relation is loaded
        if ($this->ecoProduct->relationLoaded('category') && $this->ecoProduct->category) {
            $data['category'] = [
                'id' => $this->ecoProduct->category->id,
                'name' => $this->ecoProduct->category->name,
            ];
        } else {
            $data['category'] = null;
        }

        // Only include subCategory if relation is loaded
        if ($this->ecoProduct->relationLoaded('subCategory') && $this->ecoProduct->subCategory) {
            $data['sub_category'] = [
                'id' => $this->ecoProduct->subCategory->id,
                'name' => $this->ecoProduct->subCategory->name,
            ];
        } else {
            $data['sub_category'] = null;
        }

        // Only include subSubCategory if relation is loaded
        if ($this->ecoProduct->relationLoaded('subSubCategory') && $this->ecoProduct->subSubCategory) {
            $data['sub_sub_category'] = [
                'id' => $this->ecoProduct->subSubCategory->id,
                'name' => $this->ecoProduct->subSubCategory->name,
            ];
        } else {
            $data['sub_sub_category'] = null;
        }

        // Only include brand if relation is loaded
        if ($this->ecoProduct->relationLoaded('brand') && $this->ecoProduct->brand) {
            $data['brand'] = [
                'id' => $this->ecoProduct->brand->id,
                'name' => $this->ecoProduct->brand->name,
            ];
        } else {
            $data['brand'] = null;
        }

        // Only include countries if relation is loaded
        if ($this->ecoProduct->relationLoaded('countries')) {
            $data['countries'] = $this->ecoProduct->countries->map(function ($country) {
                return [
                    'id' => $country->id,
                    'name' => $country->name,
                ];
            })->values()->all();
        } else {
            $data['countries'] = null;
        }

        return $data;
    }
}

