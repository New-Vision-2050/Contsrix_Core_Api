<?php

declare(strict_types=1);

namespace Modules\Shared\TimeZone\Presenters;

use Modules\Shared\TimeZone\Models\TimeZone;
use BasePackage\Shared\Presenters\AbstractPresenter;

class TimeZonePresenter extends AbstractPresenter
{
    private TimeZone $timeZone;

    public function __construct(TimeZone $timeZone)
    {
        $this->timeZone = $timeZone;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->timeZone->id,
            'time_zone' => $this->timeZone->time_zone,
            'country'=> $this->timeZone->country->name,
        ];
    }
}
