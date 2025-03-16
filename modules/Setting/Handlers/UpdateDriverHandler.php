<?php

declare(strict_types=1);

namespace Modules\Setting\Handlers;

use Modules\Setting\Commands\Drivers\DriverCommand;
use Modules\Setting\Commands\UpdateLoginWayCommand;
use Modules\Setting\Repositories\DriverRepository;
use Modules\Setting\Repositories\LoginWayRepository;
use Modules\Setting\Repositories\SettingRepository;
use Ramsey\Uuid\UuidInterface;

class UpdateDriverHandler
{
    public function __construct(
        private DriverRepository  $repository,
    ) {
    }

    public function handle(DriverCommand $driverCommand)
    {
        $this->repository->update($driverCommand->getId(), ["config"=>$driverCommand->toArray()]);
    }
}
