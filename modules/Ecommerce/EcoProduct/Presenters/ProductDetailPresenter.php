<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Presenters;

use Modules\Ecommerce\EcoCategory\Presenters\EcoCategoryPresenter;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\EcoBrand\Presenters\EcoBrandPresenter;
use Modules\Ecommerce\EcoProduct\Models\ProductDetail;

class ProductDetailPresenter extends AbstractPresenter
{
    private ProductDetail $productDetail;

    public function __construct(ProductDetail $productDetail)
    {
        $this->productDetail = $productDetail;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->productDetail->id,
            'label' => $this->productDetail->label,
            'value' => $this->productDetail->value,
        ];
    }
}
