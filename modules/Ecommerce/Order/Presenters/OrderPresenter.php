<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Order\Presenters;

use Modules\Ecommerce\Order\Models\Order;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\Warehous\Presenters\WarehousPresenter;

class OrderPresenter extends AbstractPresenter
{
    private Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->order->id,
            'order_serial' => $this->order->order_serial,
            'order_number' => $this->order->order_number,
            'order_date' => $this->order->order_date,
            'customer_info' => $this->order->customer_info,
            'total_price' => $this->order->total_price,
            'order_status' => [
                'order_status' => $this->order->order_status,
                'payment_status' => $this->order->payment_status,
            ],

            'order_amount' => $this->order->order_amount,
            'paid_amount' => $this->order->paid_amount,
            'discount_amount' => $this->order->discount_amount,
            'shipping_cost' => $this->order->shipping_cost,
            'payment_method' => $this->order->payment_method,
            'shipping_address' => $this->order->shipping_address,
            'order_note' => $this->order->order_note,
            'expected_delivery_date' => $this->order->expected_delivery_date?->format('Y-m-d'),
            'created_at' => $this->order->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
