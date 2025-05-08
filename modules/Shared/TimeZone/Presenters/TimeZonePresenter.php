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
            'zone_name' => $this->timeZone->zone_name,
            'gmt_offset'=> $this->timeZone->gmt_offset,
            'gmt_offset_name'=> $this->timeZone->gmt_offset_name,
            'abbreviation'=> $this->timeZone->abbreviation,
            'tz_name'=> $this->timeZone->tz_name,
        ];
    }
}
