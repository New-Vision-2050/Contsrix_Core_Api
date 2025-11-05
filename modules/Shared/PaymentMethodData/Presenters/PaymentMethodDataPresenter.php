<?php

declare(strict_types=1);

namespace Modules\Shared\PaymentMethodData\Presenters;

use Modules\Shared\PaymentMethodData\Models\PaymentMethodData;
use BasePackage\Shared\Presenters\AbstractPresenter;

class PaymentMethodDataPresenter extends AbstractPresenter
{
    private PaymentMethodData $paymentMethodData;

    public function __construct(PaymentMethodData $paymentMethodData)
    {
        $this->paymentMethodData = $paymentMethodData;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->paymentMethodData->id,
            'type' => $this->paymentMethodData->type,
            'name' => $this->paymentMethodData->name,

        ];
    }
}
