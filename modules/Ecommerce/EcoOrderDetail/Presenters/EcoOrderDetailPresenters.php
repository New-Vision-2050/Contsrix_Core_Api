<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrderDetail\Presenters;

use Modules\Ecommerce\EcoOrderDetail\Models\EcoOrderDetail;
use BasePackage\Shared\Presenters\AbstractPresenter;

class EcoOrderDetailPresenter extends AbstractPresenter
{
    private EcoOrderDetail $ecoOrderDetail;

    public function __construct(EcoOrderDetail $ecoOrderDetail)
    {
        $this->ecoOrderDetail = $ecoOrderDetail;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->ecoOrderDetail->id,
            'name' => $this->ecoOrderDetail->name,
        ];
    }
}
