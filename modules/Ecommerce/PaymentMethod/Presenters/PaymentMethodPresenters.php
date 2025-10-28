<?php

declare(strict_types=1);

namespace Modules\Ecommerce\PaymentMethod\Presenters;

use Modules\Ecommerce\PaymentMethod\Models\PaymentMethod;
use BasePackage\Shared\Presenters\AbstractPresenter;

class PaymentMethodPresenters extends AbstractPresenter
{
    private PaymentMethod $paymentMethod;

    public function __construct(PaymentMethod $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->paymentMethod->id,
            'name' => $this->paymentMethod->name,
        ];
    }
}
