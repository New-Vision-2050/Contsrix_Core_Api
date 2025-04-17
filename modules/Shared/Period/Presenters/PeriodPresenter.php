<?php

declare(strict_types=1);

namespace Modules\Shared\Period\Presenters;

use Modules\Shared\Period\Models\Period;
use BasePackage\Shared\Presenters\AbstractPresenter;

class PeriodPresenter extends AbstractPresenter
{
    private Period $period;

    public function __construct(Period $period)
    {
        $this->period = $period;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->period->id,
            'name' => $this->period->name,
        ];
    }
}
