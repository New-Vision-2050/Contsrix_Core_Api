<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrderDetail\Presenters;

use Modules\Ecommerce\EcoOrderDetail\Models\EcoOrderDetail;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\EcoOrder\Presenters\EcoOrderDetailsPresenter;
use Modules\Ecommerce\EcoOrder\Presenters\EcoOrderPresenter;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\Securities\Price;

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
            'eco_product_id' => $this->ecoOrderDetail->eco_product_id,
            'qty' => $this->ecoOrderDetail->qty,
            'price' => $this->ecoOrderDetail->price,
            'amount'=> $this->ecoOrderDetail->amount,
            'product_details' => $this->ecoOrderDetail->product_details,
            'order' => $this->ecoOrderDetail->order? (new EcoOrderDetailsPresenter($this->ecoOrderDetail->order))->getData():null

        ];
    }
}
