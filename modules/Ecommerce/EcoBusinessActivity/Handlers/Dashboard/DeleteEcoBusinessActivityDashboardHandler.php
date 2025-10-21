<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBusinessActivity\Handlers\Dashboard;

use Modules\Ecommerce\EcoBusinessActivity\Repositories\EcoBusinessActivityRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteEcoBusinessActivityDashboardHandler
{
    public function __construct(
        private EcoBusinessActivityRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteEcoBusinessActivity($id);
    }
}
