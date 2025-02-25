<?php

declare(strict_types=1);

namespace Modules\Setting\Presenters;

use Modules\Setting\Models\Driver;
use Modules\Setting\Models\LoginWayStep;
use Modules\Setting\Models\Setting;
use BasePackage\Shared\Presenters\AbstractPresenter;

class DriverPresenter extends AbstractPresenter
{

    public function __construct(public Driver $driver)
    {
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'type' => $this->driver->driver_type,
            'name' => $this->driver->name,
        ];
    }
}
