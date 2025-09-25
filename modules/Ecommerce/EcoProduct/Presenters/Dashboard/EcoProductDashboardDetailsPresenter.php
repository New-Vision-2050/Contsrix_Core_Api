<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Presenters\Dashboard;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use Modules\Ecommerce\EcoBrand\Presenters\EcoBrandPresenter;
use Modules\Ecommerce\EcoCategory\Presenters\EcoCategoryPresenter;
use Modules\Shared\Media\Presenters\MediaPresenter;

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
            'category' => $this->ecoProduct->category ? (new EcoCategoryPresenter($this->ecoProduct->category))->getData() : null,
            'brand' => $this->ecoProduct->brand ? (new EcoBrandPresenter($this->ecoProduct->brand))->getData() : null,
            'type' => $this->ecoProduct->type,
            'taxes' => ProductTaxDashboardPresenter::collection($this->ecoProduct->taxes),
            'details' => ProductDetailDashboardPresenter::collection($this->ecoProduct->details),
            'custom_fields' => ProductCustomFieldDashboardPresenter::collection($this->ecoProduct->customFields),
            'seo' => $this->ecoProduct->seo ? (new ProductSEODashboardPresenter($this->ecoProduct->seo))->getData() : null,
            'associated_product' => EcoProductDashboardPresenter::collection($this->ecoProduct->associatedProducts),
            'main_image' => $firstMedia ? (new MediaPresenter($firstMedia))->getData() : null,
            'other_images' => MediaPresenter::collection($this->ecoProduct->getMedia('eco_product_other_image')),
        ];
    }
}
