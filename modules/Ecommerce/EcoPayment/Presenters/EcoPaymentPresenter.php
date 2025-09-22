<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoPayment\Presenters;

use Modules\Ecommerce\EcoPayment\Models\EcoPayment;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\Payment\Presenters\PaymentPresenter;

class EcoPaymentPresenter extends AbstractPresenter
{
    private EcoPayment $ecoPayment;

    public function __construct(EcoPayment $ecoPayment)
    {
        $this->ecoPayment = $ecoPayment;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->ecoPayment->id,
            'company_id' => $this->ecoPayment->company_id,
            'payment' => $this->ecoPayment->payment ?
                (new PaymentPresenter($this->ecoPayment->payment))->getData() : null,
            'is_default' => (int) $this->ecoPayment->is_default,
            'is_active' => (int) $this->ecoPayment->is_active,
            'created_at' => $this->ecoPayment->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->ecoPayment->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
