<?php

declare(strict_types=1);

namespace Modules\Ecommerce\DealDay\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\DealDay\Models\DealDay;
use Modules\Company\CompanyCore\Presenters\CompanyPresenter;
use Modules\Ecommerce\EcoProduct\Presenters\Dashboard\EcoProductDashboardPresenter;

class DealDayPresenter extends AbstractPresenter
{
    private DealDay $dealDay;

    public function __construct(DealDay $dealDay)
    {
        $this->dealDay = $dealDay;
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->dealDay->id,
            'name' => $isListing 
                ? $this->dealDay->name 
                : [
                    'ar' => $this->dealDay->getTranslation('name', 'ar'),
                    'en' => $this->dealDay->getTranslation('name', 'en'),
                ],
            'product' => $this->dealDay->product 
                ? (new EcoProductDashboardPresenter($this->dealDay->product))->getData()
                : null,
            'discount_type' => $this->dealDay->discount_type,
            'discount_value' => $this->dealDay->discount_value,
            'is_active' => (int) $this->dealDay->is_active,
        ];

        return $data;
    }

    
}
