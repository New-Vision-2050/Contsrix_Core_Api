<?php

declare(strict_types=1);

namespace Modules\Setting\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class DriverFilter extends SearchModelFilter
{
    public $relations = [];

    public function driverType($type)
    {
      return  $this->where("driver_type", $type);
    }



}
