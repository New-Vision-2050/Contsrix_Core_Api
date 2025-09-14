<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Presenters;

use Modules\Ecommerce\EcoCategory\Presenters\EcoCategoryPresenter;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\EcoBrand\Presenters\EcoBrandPresenter;
use Modules\Shared\Media\Presenters\MediaPresenter;

class EcoProductPresenter extends AbstractPresenter
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
            'name' => $this->ecoProduct->name,
            'price' => $this->ecoProduct->price,
            'stock' => $this->ecoProduct->stock,
            'sku' => $this->ecoProduct->sku,
            'is_visible' => (int)$this->ecoProduct->is_visible,
            'main_image' => $firstMedia ? (new MediaPresenter($firstMedia))->getData() : null,

        ];
    }
}
