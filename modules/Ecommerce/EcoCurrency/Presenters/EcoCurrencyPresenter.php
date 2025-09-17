<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCurrency\Presenters;

use Modules\Ecommerce\EcoCurrency\Models\EcoCurrency;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\Currency\Presenters\CurrencyPresenter;

class EcoCurrencyPresenter extends AbstractPresenter
{
    private EcoCurrency $ecoCurrency;

    public function __construct(EcoCurrency $ecoCurrency)
    {
        $this->ecoCurrency = $ecoCurrency;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->ecoCurrency->id,
            'company_id' => $this->ecoCurrency->company_id,
            'currency' => $this->ecoCurrency->currency ? (new CurrencyPresenter($this->ecoCurrency->currency))->getData() : null,
            'is_default' => (int) $this->ecoCurrency->is_default,
            'is_active' => (int) $this->ecoCurrency->is_active,
        ];
    }
}
