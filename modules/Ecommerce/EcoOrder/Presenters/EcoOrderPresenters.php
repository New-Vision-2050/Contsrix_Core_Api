<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrder\Presenters;

use Modules\Ecommerce\EcoOrder\Models\EcoOrder;
use BasePackage\Shared\Presenters\AbstractPresenter;

class EcoOrderPresenter extends AbstractPresenter
{
    private EcoOrder $ecoOrder;

    public function __construct(EcoOrder $ecoOrder)
    {
        $this->ecoOrder = $ecoOrder;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->ecoOrder->id,
            'name' => $this->ecoOrder->name,
        ];
    }
}
