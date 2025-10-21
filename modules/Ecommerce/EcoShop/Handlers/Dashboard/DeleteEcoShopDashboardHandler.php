<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShop\Handlers\Dashboard;

use Modules\Ecommerce\EcoShop\Repositories\EcoShopRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteEcoShopDashboardHandler
{
    public function __construct(
        private EcoShopRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteEcoShop($id);
    }
}
