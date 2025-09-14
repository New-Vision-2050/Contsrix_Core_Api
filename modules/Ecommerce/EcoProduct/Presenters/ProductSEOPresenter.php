<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Presenters;

use Modules\Ecommerce\EcoCategory\Presenters\EcoCategoryPresenter;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\EcoBrand\Presenters\EcoBrandPresenter;
use Modules\Ecommerce\EcoProduct\Models\ProductSEO;

class ProductSEOPresenter extends AbstractPresenter
{
    private ProductSEO $productSEO;

    public function __construct(ProductSEO $productSEO)
    {
        $this->productSEO = $productSEO;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->productSEO->id,
            'meta_title' => $this->productSEO->meta_title,
            'meta_description' => $this->productSEO->meta_description,
            'meta_keywords' => $this->productSEO->meta_keywords,
        ];
    }
}
