<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Order\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class OrderFilter extends SearchModelFilter
{
    public $relations = ['customer', 'warehouse', 'orderDetails'];

    // Search functionality
    public function search($search)
    {
        return $this->where(function ($query) use ($search) {
            $query->where('order_serial', 'like', "%{$search}%")
                  ->orWhere('order_number', 'like', "%{$search}%")
                  ->orWhere('shipping_address', 'like', "%{$search}%")
                  ->orWhere('order_note', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
        });
    }

    // Order status filters
    public function orderStatus($status)
    {
        $validStatuses = [
            'pending',
            'confirmed', 
            'processing',
            'out_for_delivery',
            'delivered',
            'returned',
            'failed',
            'canceled'
        ];

        if (in_array($status, $validStatuses)) {
            return $this->where('order_status', $status);
        }

        return $this;
    }

    // Payment status filters
    public function paymentStatus($status)
    {
        $validPaymentStatuses = ['paid', 'unpaid', 'partial', 'refunded'];
        
        if (in_array($status, $validPaymentStatuses)) {
            return $this->where('payment_status', $status);
        }

        return $this;
    }

    // Customer type filter
    public function customerType($type)
    {
        $validTypes = ['guest', 'registered'];
        
        if (in_array($type, $validTypes)) {
            return $this->where('customer_type', $type);
        }

        return $this;
    }

    // Date range filters
    public function dateFrom($date)
    {
        return $this->whereDate('created_at', '>=', $date);
    }

    public function dateTo($date)
    {
        return $this->whereDate('created_at', '<=', $date);
    }

    // Amount range filters
    public function minAmount($amount)
    {
        return $this->where('order_amount', '>=', $amount);
    }

    public function maxAmount($amount)
    {
        return $this->where('order_amount', '<=', $amount);
    }

    // Warehouse filter
    public function warehouseId($warehouseId)
    {
        return $this->where('warehouse_id', $warehouseId);
    }

    // Customer filter
    public function customerId($customerId)
    {
        return $this->where('customer_id', $customerId);
    }

    // Is guest filter
    public function isGuest($isGuest = true)
    {
        return $this->where('is_guest', $isGuest);
    }

    // Multiple status filter
    public function statuses($statuses)
    {
        if (is_array($statuses) && !empty($statuses)) {
            return $this->whereIn('order_status', $statuses);
        }

        return $this;
    }

    // Expected delivery date filter
    public function expectedDeliveryDate($date)
    {
        return $this->whereDate('expected_delivery_date', $date);
    }

    // Orders with items filter
    public function hasItems()
    {
        return $this->whereHas('orderDetails');
    }

    // Recent orders (last N days)
    public function recent($days = 7)
    {
        return $this->where('created_at', '>=', now()->subDays($days));
    }
}
