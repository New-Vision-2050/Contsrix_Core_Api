<?php

declare(strict_types=1);

namespace Modules\Ecommerce\OrderTransaction\Presenters;

use Modules\Ecommerce\OrderTransaction\Models\OrderTransaction;
use BasePackage\Shared\Presenters\AbstractPresenter;

class OrderTransactionPresenter extends AbstractPresenter
{
    private OrderTransaction $orderTransaction;

    public function __construct(OrderTransaction $orderTransaction)
    {
        $this->orderTransaction = $orderTransaction;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->orderTransaction->id,
            'name' => $this->orderTransaction->name,
        ];
    }
}
