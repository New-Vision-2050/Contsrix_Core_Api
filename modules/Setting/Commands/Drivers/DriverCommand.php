<?php

namespace Modules\Setting\Commands\Drivers;

interface DriverCommand
{
    public function toArray();
    public function getId();
}
