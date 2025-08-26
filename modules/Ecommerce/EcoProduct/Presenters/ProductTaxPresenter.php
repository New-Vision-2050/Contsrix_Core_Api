<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Country\Presenters\CountryPresenter;
use Modules\Ecommerce\EcoProduct\Models\ProductTax;

class ProductTaxPresenter extends AbstractPresenter
{
    private ProductTax $productTax;

    public function __construct(ProductTax $productTax)
    {
        $this->productTax = $productTax;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->productTax->id,
            'country' => (new CountryPresenter($this->productTax->country))->getData(), //$this->productTax->country,
            'tax_number' => $this->productTax->tax_number,
            'tax_percentage' => $this->productTax->tax_percentage,
            'is_active' => (int) $this->productTax->is_active,
        ];
    }
}
