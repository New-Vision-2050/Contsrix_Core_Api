<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FlashDeal\Presenters;

use Modules\Ecommerce\FlashDeal\Models\FlashDeal;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\EcoProduct\Presenters\Dashboard\EcoProductDashboardPresenter;
use Modules\Shared\Media\Presenters\MediaPresenter;
class FlashDealPresenter extends AbstractPresenter
{
    private FlashDeal $flashDeal;

    public function __construct(FlashDeal $flashDeal)
    {
        $this->flashDeal = $flashDeal;
    }

    protected function present(bool $isListing = false): array
    {
        $media = $this->flashDeal->getFirstMedia('upload');

        $data = [
            'id' => $this->flashDeal->id,
            'company_id' => $this->flashDeal->company_id,
            'name' => $isListing 
                ? $this->flashDeal->name 
                : [
                    'ar' => $this->flashDeal->getTranslation('name', 'ar'),
                    'en' => $this->flashDeal->getTranslation('name', 'en'),
                ],
            'start_date' => $this->flashDeal->start_date?->format('Y-m-d H:i:s'),
            'end_date' => $this->flashDeal->end_date?->format('Y-m-d H:i:s'),
            'is_active' => (int) $this->flashDeal->is_active,
            "file" => $media != null ? (new MediaPresenter($media))->getData() : null,
        ];

        // Only include products if the relation is loaded
        if ($this->flashDeal->relationLoaded('products')) {
            $data['products'] = EcoProductDashboardPresenter::collection($this->flashDeal->products);
        }

        return $data;
    }
}
