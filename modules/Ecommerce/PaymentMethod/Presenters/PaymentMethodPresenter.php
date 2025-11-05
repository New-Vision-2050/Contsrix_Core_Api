<?php

declare(strict_types=1);

namespace Modules\Ecommerce\PaymentMethod\Presenters;

use Modules\Ecommerce\PaymentMethod\Models\PaymentMethod;
use BasePackage\Shared\Presenters\AbstractPresenter;

class PaymentMethodPresenter extends AbstractPresenter
{
    private $paymentMethod;

    public function __construct($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    protected function present(bool $isListing = false): array
    {
        $data = is_object($this->paymentMethod) ? $this->paymentMethod : (object) $this->paymentMethod;
        
        return [
            'id' => $data->id ?? null,
            'type' => $data->type ?? null,
            'name' => $data->name ?? null,
            'is_active' => isset($data->is_active) ? (int) $data->is_active : 0,
        ];
    }
}
