<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoDiscount\Presenters;

use Modules\Ecommerce\EcoDiscount\Models\EcoDiscount;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;

class EcoDiscountProductPresenter extends AbstractPresenter
{
    private EcoProduct $ecoProduct;

    public function __construct(EcoProduct $ecoProduct)
    {
        $this->ecoProduct = $ecoProduct;
    }

    protected function present(bool $isListing = false): array
    {
    return
        [
            'id' => $this->ecoProduct->id,
            'name' => $this->ecoProduct->name,
            'has_discount' => (int) $this->ecoProduct->has_discount,
            'discount_amount' => $this->ecoProduct->discount_amount,
            'discount_percentage' => $this->ecoProduct->discount_percentage,
            'max_discount_amount' => $this->ecoProduct->max_discount_amount,
            'discount_start_date' => $this->ecoProduct->discount_start_date,
            'discount_end_date' => $this->ecoProduct->discount_end_date,
        ];
    }
}
