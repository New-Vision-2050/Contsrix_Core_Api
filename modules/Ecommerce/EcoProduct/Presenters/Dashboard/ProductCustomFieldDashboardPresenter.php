<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Presenters\Dashboard;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\EcoProduct\Models\ProductCustomField;

class ProductCustomFieldDashboardPresenter extends AbstractPresenter
{
    private ProductCustomField $productCustomField;

    public function __construct(ProductCustomField $productCustomField)
    {
        $this->productCustomField = $productCustomField;
    }


    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->productCustomField->id,
            'field_name' => $this->productCustomField->field_name,
            'field_value' => $this->productCustomField->field_value,
        ];
    }
}
