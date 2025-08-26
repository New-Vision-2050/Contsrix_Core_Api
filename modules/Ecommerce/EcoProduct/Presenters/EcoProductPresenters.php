<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Presenters;

use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use BasePackage\Shared\Presenters\AbstractPresenter;

class EcoProductPresenter extends AbstractPresenter
{
    private EcoProduct $ecoProduct;

    public function __construct(EcoProduct $ecoProduct)
    {
        $this->ecoProduct = $ecoProduct;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->ecoProduct->id,
            'name' => $this->ecoProduct->name,
        ];
    }
}
