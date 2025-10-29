<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Dashboard\Handlers;

use Modules\Ecommerce\Dashboard\Commands\UpdateDashboardCommand;
use Modules\Ecommerce\Dashboard\Repositories\DashboardRepository;

class UpdateDashboardHandler
{
    public function __construct(
        private DashboardRepository $repository,
    ) {
    }

    public function handle(UpdateDashboardCommand $updateDashboardCommand)
    {
        $this->repository->updateDashboard($updateDashboardCommand->getId(), $updateDashboardCommand->toArray());
    }
}
