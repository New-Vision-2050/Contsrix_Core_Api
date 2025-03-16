<?php

declare(strict_types=1);

namespace Modules\Setting\Handlers;

use Modules\Setting\Models\LoginWay;
use Modules\Setting\Repositories\LoginWayRepository;
use Modules\Setting\Repositories\SettingRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteLoginWayHandler
{
    public function __construct(
        private LoginWayRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteLoginWay($id);
    }
}
