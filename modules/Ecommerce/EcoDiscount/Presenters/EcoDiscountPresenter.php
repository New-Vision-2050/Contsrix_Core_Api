<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoDiscount\Presenters;

use Modules\Ecommerce\EcoDiscount\Models\EcoDiscount;
use BasePackage\Shared\Presenters\AbstractPresenter;

class EcoDiscountPresenter extends AbstractPresenter
{
    private EcoDiscount $ecoDiscount;

    public function __construct(EcoDiscount $ecoDiscount)
    {
        $this->ecoDiscount = $ecoDiscount;
    }

    protected function present(bool $isListing = false): array
    {
        $status = 'نشط';
        $statusColor = 'success';

        if (!$this->ecoDiscount->is_active) {
            $status = 'غير نشط';
            $statusColor = 'error';
        } elseif ($this->ecoDiscount->end_date && $this->ecoDiscount->end_date->lt(now())) {
            $status = 'منتهي';
            $statusColor = 'warning';
        } elseif ($this->ecoDiscount->usage_limit && $this->ecoDiscount->used_count >= $this->ecoDiscount->usage_limit) {
            $status = 'مستنفد';
            $statusColor = 'warning';
        }

        $discountValue = $this->ecoDiscount->type === 'percentage'
            ? $this->ecoDiscount->value . '%'
            : number_format($this->ecoDiscount->value, 0) . ' ريال';

        $baseData = [
            'id' => $this->ecoDiscount->id,
            'name' => $this->ecoDiscount->name,
            'description' => $this->ecoDiscount->description,
            'code' => $this->ecoDiscount->code,
            'type' => $this->ecoDiscount->type,
            'value' => $discountValue,
            'raw_value' => $this->ecoDiscount->value,
            'min_order_amount' => $this->ecoDiscount->min_order_amount,
            'max_discount_amount' => $this->ecoDiscount->max_discount_amount,
            'usage_limit' => $this->ecoDiscount->usage_limit,
            'used_count' => $this->ecoDiscount->used_count,
            'usage' => $this->ecoDiscount->used_count . '/' . ($this->ecoDiscount->usage_limit ?? '∞'),
            'start_date' => $this->ecoDiscount->start_date?->format('Y-m-d H:i:s'),
            'end_date' => $this->ecoDiscount->end_date?->format('Y-m-d H:i:s'),
             'is_active' =>(int) $this->ecoDiscount->is_active,
            'applies_to' => $this->ecoDiscount->applies_to,
            'status' => $status,
            'status_color' => $statusColor,
            'created_at' => $this->ecoDiscount->created_at->format('Y-m-d H:i:s'),
        ];

        if (!$isListing) {
            $baseData['products'] = $this->ecoDiscount->products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => number_format($product->price, 0) . ' ريال',
                    'stock' => $product->stock,
                ];
            });
        } else {
            $baseData['products_count'] = $this->ecoDiscount->products->count();
        }

        return $baseData;
    }
}
