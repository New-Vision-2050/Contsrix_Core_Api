<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Dashboard\Handlers;

use Modules\Ecommerce\Dashboard\Repositories\DashboardRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteDashboardHandler
{
    public function __construct(
        private DashboardRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteDashboard($id);
    }
}
