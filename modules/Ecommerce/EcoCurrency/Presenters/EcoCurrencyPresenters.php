<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCurrency\Presenters;

use Modules\Ecommerce\EcoCurrency\Models\EcoCurrency;
use BasePackage\Shared\Presenters\AbstractPresenter;

class EcoCurrencyPresenters extends AbstractPresenter
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
            'name' => $this->ecoCurrency->name,
        ];
    }
}
