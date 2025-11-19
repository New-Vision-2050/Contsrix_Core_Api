<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FeatureDeal\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\FeatureDeal\Models\FeatureDeal;
use Modules\Ecommerce\EcoProduct\Presenters\Dashboard\EcoProductDashboardPresenter;

class FeatureDealPresenter extends AbstractPresenter
{
    private FeatureDeal $featureDeal;

    public function __construct(FeatureDeal $featureDeal)
    {
        $this->featureDeal = $featureDeal;
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->featureDeal->id,
            'name' => $isListing 
                ? $this->featureDeal->name 
                : [
                    'ar' => $this->featureDeal->getTranslation('name', 'ar'),
                    'en' => $this->featureDeal->getTranslation('name', 'en'),
                ],
            'start_date' => $this->featureDeal->start_date?->toDateString(),
            'end_date' => $this->featureDeal->end_date?->toDateString(),
            'discount_type' => $this->featureDeal->discount_type,
            'discount_value' => $this->featureDeal->discount_value,
            'is_active' => (int) $this->featureDeal->is_active,
        ];

        // Only include products if the relation is loaded
        if ($this->featureDeal->relationLoaded('products')) {
            $data['products'] = EcoProductDashboardPresenter::collection($this->featureDeal->products);
        }

        return $data;
    }
}
