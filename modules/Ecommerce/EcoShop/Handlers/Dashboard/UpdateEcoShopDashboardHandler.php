<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShop\Handlers\Dashboard;

use Modules\Ecommerce\EcoShop\Commands\Dashboard\UpdateEcoShopDashboardCommand;
use Modules\Ecommerce\EcoShop\Repositories\EcoShopRepository;

class UpdateEcoShopDashboardHandler
{
    public function __construct(
        private EcoShopRepository $repository,
    ) {
    }

    public function handle(UpdateEcoShopDashboardCommand $updateEcoShopCommand)
    {
        $this->repository->updateEcoShop($updateEcoShopCommand->getId(), $updateEcoShopCommand->toArray());
    }
}
