<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Warehous\Handlers;

use Modules\Ecommerce\Warehous\Commands\UpdateWarehousCommand;
use Modules\Ecommerce\Warehous\Repositories\WarehousRepository;

class UpdateWarehousHandler
{
    public function __construct(
        private WarehousRepository $repository,
    ) {
    }

    public function handle(UpdateWarehousCommand $updateWarehousCommand)
    {
        $this->repository->updateWarehous($updateWarehousCommand->getId(), $updateWarehousCommand->toArray());
    }
}
