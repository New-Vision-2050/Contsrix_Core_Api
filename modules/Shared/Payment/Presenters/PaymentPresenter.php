<?php

declare(strict_types=1);

namespace Modules\Shared\Payment\Presenters;

use Modules\Shared\Payment\Models\Payment;
use BasePackage\Shared\Presenters\AbstractPresenter;

class PaymentPresenter extends AbstractPresenter
{
    private Payment $payment;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->payment->id,
            'name' => $this->payment->name,
        ];
    }
}
