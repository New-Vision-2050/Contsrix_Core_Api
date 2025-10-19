<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Handlers\Dashboard;

use Modules\Ecommerce\EcoCategory\Repositories\EcoCategoryRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteEcoCategoryDashboardHandler
{
    public function __construct(
        private EcoCategoryRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteEcoCategory($id);
    }
}
