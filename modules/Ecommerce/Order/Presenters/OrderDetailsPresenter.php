<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Order\Presenters;

use Modules\Ecommerce\Order\Models\Order;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\Warehous\Presenters\WarehousPresenter;

class OrderDetailsPresenter extends AbstractPresenter
{
    private Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            // معلومات الطلب الأساسية
            'order_info' => [
                'id' => $this->order->id,
                'order_serial' => $this->order->order_serial,
                'order_number' => $this->order->order_number,
                'order_group_id' => $this->order->order_group_id,
                'order_date' => $this->order->created_at->format('Y/m/d'),
                'order_time' => $this->order->created_at->format('H:i'),
            ],

            // معلومات العميل
            'customer_info' => $this->order->customer_info,

            // حالة الطلب
            'order_status' => [
                'order_status' => $this->order->order_status,
                'payment_status' => $this->order->payment_status,
                'delivery_status' => $this->getDeliveryStatus(),
            ],

            // المبالغ المالية
            'financial_info' => [
                'order_amount' => (float) $this->order->order_amount,
                'paid_amount' => (float) $this->order->paid_amount,
                'discount_amount' => (float) $this->order->discount_amount,
                'shipping_cost' => (float) $this->order->shipping_cost,
                'tax_amount' => $this->calculateTotalTax(),
                'total_amount' => $this->calculateTotalAmount(),
            ],

            // معلومات الدفع والشحن
            'payment_shipping_info' => [
                'payment_method' => $this->order->payment_method,
                'transaction_ref' => $this->order->transaction_ref,
                'shipping_address' => $this->order->shipping_address,
                'shipping_address_data' => $this->order->shipping_address_data ? json_decode($this->order->shipping_address_data, true) : null,
                'billing_address_data' => $this->order->billing_address_data ? json_decode($this->order->billing_address_data, true) : null,
                'expected_delivery_date' => $this->order->expected_delivery_date,
                'shipping_type' => $this->order->shipping_type,
                'delivery_type' => $this->order->delivery_type,
            ],

            // تفاصيل المنتجات
            'order_items' => $this->getOrderItems(),

            // ملاحظات وتفاصيل إضافية
            'additional_info' => [
                'order_note' => $this->order->order_note,
                'order_type' => $this->order->order_type,
                'verification_code' => $this->order->verification_code,
                'verification_status' => $this->order->verification_status,
            ],

            'timestamps' => [
                'created_at' => $this->order->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $this->order->updated_at->format('Y-m-d H:i:s'),
            ],

            // إحصائيات سريعة
            'summary' => [
                'total_items' => $this->order->orderDetails->count(),
                'total_quantity' => $this->order->orderDetails->sum('qty'),
                'items_delivered' => $this->order->orderDetails->where('delivery_status', 'delivered')->count(),
                'items_pending' => $this->order->orderDetails->where('delivery_status', 'pending')->count(),
            ],
        ];
    }

    private function getDeliveryStatus(): string
    {
        $orderDetails = $this->order->orderDetails;
        
        if ($orderDetails->isEmpty()) {
            return 'pending';
        }
        
        $deliveredCount = $orderDetails->where('delivery_status', 'delivered')->count();
        $totalCount = $orderDetails->count();
        
        if ($deliveredCount === $totalCount) {
            return 'delivered';
        } elseif ($deliveredCount > 0) {
            return 'partially_delivered';
        } else {
            return 'pending';
        }
    }

    private function calculateTotalTax(): float
    {
        return (float) $this->order->orderDetails->sum('tax');
    }

    private function calculateTotalAmount(): float
    {
        return (float) ($this->order->order_amount + $this->order->shipping_cost);
    }

    private function getOrderItems(): array
    {
        return $this->order->orderDetails->map(function ($item) {
            $productDetails = $item->product_details ? json_decode($item->product_details, true) : [];
            
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $productDetails['product_name'] ?? 'منتج غير محدد',
                'product_sku' => $productDetails['product_sku'] ?? null,
                'product_image' => $productDetails['main_photo'] ?? null,
                'quantity' => $item->qty,
                'unit_price' => (float) $item->price,
                'total_price' => (float) ($item->price * $item->qty),
                'tax_amount' => (float) $item->tax,
                'discount_amount' => (float) $item->discount,
                'delivery_status' => $item->delivery_status,
                'payment_status' => $item->payment_status,
                'warehouse_info' => [
                    'warehouse_id' => $item->warehouse_id,
                    'warehouse_name' => $item->warehouse?->name ?? 'مخزن غير محدد',
                ],
                'product_details' => $productDetails,
                'variant' => $item->variant,
                'variation' => $item->variation,
                'is_stock_decreased' => $item->is_stock_decreased,
                'refund_request' => $item->refund_request,
            ];
        })->toArray();
    }
}
