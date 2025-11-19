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
        $mainImage = $this->ecoProduct->getFirstMedia('eco_product_main_image');
        $originalPrice = (float) $this->ecoProduct->price;
        $finalPrice = (float) ($this->ecoProduct->final_price ?? $originalPrice);
        $hasDiscount = (int) ($this->ecoProduct->has_active_discount ?? false);
        $discountPercentage = $originalPrice > 0
            ? round((($originalPrice - $finalPrice) / $originalPrice) * 100, 0)
            : 0;

        return [
            'id' => $this->ecoProduct->id,
            'name' => $this->ecoProduct->name,
            'price' => $originalPrice,
            'final_price' => $finalPrice,
            'discount_percentage' => $discountPercentage,
            'is_on_discount' => $hasDiscount,
            'is_featured' => (int) $this->ecoProduct->is_featured,
            'rating' => $this->ecoProduct->rating ?? 4.6,
            'reviews_count' => (int) $this->ecoProduct->reviews_count ?? 0,
            'main_image' => $mainImage ? (new MediaPresenter($mainImage))->getData() : null,
        ];
    }
}
