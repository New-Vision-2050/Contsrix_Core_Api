<?php

declare(strict_types=1);

namespace Modules\Shared\TimeUnit\Presenters;

use Modules\Shared\TimeUnit\Models\TimeUnit;
use BasePackage\Shared\Presenters\AbstractPresenter;

class TimeUnitPresenter extends AbstractPresenter
{
    private TimeUnit $timeUnit;

    public function __construct(TimeUnit $timeUnit)
    {
        $this->timeUnit = $timeUnit;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->timeUnit->id,
            'name' => $this->timeUnit->name,
            'code' =>$this->timeUnit->code,
        ];
    }
}
