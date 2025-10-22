<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FlashDeal\Presenters;

use Modules\Ecommerce\FlashDeal\Models\FlashDeal;
use BasePackage\Shared\Presenters\AbstractPresenter;
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

        return [
            'id' => $this->flashDeal->id,
            'company_id' => $this->flashDeal->company_id,
            'name' => $this->flashDeal->name,
            'start_date' => $this->flashDeal->start_date?->format('Y-m-d H:i:s'),
            'end_date' => $this->flashDeal->end_date?->format('Y-m-d H:i:s'),
            'is_active' => (int) $this->flashDeal->is_active,

            "file" => $media != null ? (new MediaPresenter($media))->getData() : null,
        ];
    }
}
