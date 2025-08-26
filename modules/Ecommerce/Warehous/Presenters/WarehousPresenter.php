<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Warehous\Presenters;

use Modules\Ecommerce\Warehous\Models\Warehous;
use BasePackage\Shared\Presenters\AbstractPresenter;

class WarehousPresenter extends AbstractPresenter
{
    private Warehous $warehous;

    public function __construct(Warehous $warehous)
    {
        $this->warehous = $warehous;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->warehous->id,
            'name' => $this->warehous->name,
        ];
    }
}
