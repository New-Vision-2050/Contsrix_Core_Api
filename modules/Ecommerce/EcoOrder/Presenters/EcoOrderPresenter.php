<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrder\Presenters;

use Modules\Ecommerce\EcoOrder\Models\EcoOrder;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\EcoClient\Presenters\EcoClientPresenter;

class EcoOrderPresenter extends AbstractPresenter
{
    private EcoOrder $ecoOrder;

    public function __construct(EcoOrder $ecoOrder)
    {
        $this->ecoOrder = $ecoOrder;
    }

    protected function present(bool $isListing = false): array
    {

        return [
            'id' => $this->ecoOrder->id,
            'client' => $this->ecoOrder->client? (new EcoClientPresenter($this->ecoOrder->client))->getData():null,
            'created_at'=> $this->ecoOrder->created_at->format('Y-m-d'),
            'qty' => $this->ecoOrder->details->sum('qty'),
            'order_status' => $this->ecoOrder->order_status,
            'payment_status' => $this->ecoOrder->payment_status,
            'order_amount'=> $this->ecoOrder->order_amount, 
        ];
    }
}
