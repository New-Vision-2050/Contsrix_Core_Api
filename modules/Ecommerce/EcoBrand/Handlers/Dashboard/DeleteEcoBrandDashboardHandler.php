<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBrand\Handlers\Dashboard;

use Modules\Ecommerce\EcoBrand\Repositories\EcoBrandRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteEcoBrandDashboardHandler
{
    public function __construct(
        private EcoBrandRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteEcoBrand($id);
    }
}
