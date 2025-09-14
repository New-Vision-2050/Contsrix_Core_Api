<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoDiscount\Presenters;

use Modules\Ecommerce\EcoDiscount\Models\EcoDiscount;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;

class EcoDiscountOrderPresenter extends AbstractPresenter
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


        $baseData = [
            'id' => $this->ecoDiscount->id,
            'type' => $this->ecoDiscount->type,
            'value' => $this->ecoDiscount->value,
            'type_discount' => $this->ecoDiscount->type_discount,
            'min_order_amount' => $this->ecoDiscount->min_order_amount,
            'max_discount_amount' => $this->ecoDiscount->max_discount_amount,
            'is_active' =>(int) $this->ecoDiscount->is_active,
            'status' => $status,
            'status_color' => $statusColor,
        ];

        return $baseData;
    }
}
