<?php

declare(strict_types=1);

namespace Modules\Unit\Presenters;

use Modules\Unit\Models\Unit;
use BasePackage\Shared\Presenters\AbstractPresenter;

class UnitPresenter extends AbstractPresenter
{
    private Unit $unit;

    public function __construct(Unit $unit)
    {
        $this->unit = $unit;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->unit->id,
            'name' => $this->unit->name,
        ];
    }
}
