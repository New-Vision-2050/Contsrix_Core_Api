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
            'description' => $this->ecoProduct->description,
            'price' => $this->ecoProduct->price,
            'sku' => $this->ecoProduct->sku,
            'stock' => $this->ecoProduct->stock,
            'warehouse_id' => $this->ecoProduct->warehouse_id,
            'requires_shipping' => (int)$this->ecoProduct->requires_shipping,
            'unlimited_quantity' => (int)$this->ecoProduct->unlimited_quantity,
            'is_taxable' => (int)$this->ecoProduct->is_taxable,
            'price_includes_vat' => (int)$this->ecoProduct->price_includes_vat,
            'vat_percentage' => $this->ecoProduct->vat_percentage,
            'is_visible' => (int)$this->ecoProduct->is_visible,
        ];
    }
}
