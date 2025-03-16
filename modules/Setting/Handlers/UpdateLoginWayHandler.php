<?php

declare(strict_types=1);

namespace Modules\Setting\Handlers;

use Modules\Setting\Commands\UpdateLoginWayCommand;
use Modules\Setting\Repositories\LoginWayRepository;
use Modules\Setting\Repositories\SettingRepository;
use Ramsey\Uuid\UuidInterface;

class UpdateLoginWayHandler
{
    public function __construct(
        private LoginWayRepository  $repository,
    ) {
    }

    public function handle(UpdateLoginWayCommand $loginWayCommand)
    {
        $this->repository->updateLoginWay($loginWayCommand->getId(), $loginWayCommand->toArray());
    }
}
