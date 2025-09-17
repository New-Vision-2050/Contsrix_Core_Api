<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrder\Presenters;

use Modules\Ecommerce\EcoOrder\Models\EcoOrder;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\EcoClient\Presenters\EcoClientPresenter;

class EcoOrderDetailsPresenter extends AbstractPresenter
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
            'client_id' => $this->ecoOrder->client_id,
            'created_at' => $this->ecoOrder->created_at->format('Y-m-d'),
            'qty' => $this->ecoOrder->details->sum('qty'),
            'order_status' => $this->ecoOrder->order_status,
            'payment_status' => $this->ecoOrder->payment_status,
            'order_amount' => $this->ecoOrder->order_amount,
            'payment_method' => $this->ecoOrder->payment_method,
            'payment_note' => $this->ecoOrder->payment_note,
            'shipping_address_data' => $this->ecoOrder->shipping_address_data,
        ];
    }
}
