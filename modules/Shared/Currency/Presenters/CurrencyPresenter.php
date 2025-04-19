<?php

declare(strict_types=1);

namespace Modules\Shared\Currency\Presenters;

use Modules\Shared\Currency\Models\Currency;
use BasePackage\Shared\Presenters\AbstractPresenter;

class CurrencyPresenter extends AbstractPresenter
{
    private Currency $currency;

    public function __construct(Currency $currency)
    {
        $this->currency = $currency;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->currency->id,
            'name' => $this->currency->name,
            'short_name' => $this->currency->short_name,
        ];
    }
}
