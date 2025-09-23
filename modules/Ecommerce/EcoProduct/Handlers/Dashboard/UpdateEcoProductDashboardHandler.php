<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Handlers\Dashboard;

use Modules\Ecommerce\EcoProduct\Commands\Dashboard\UpdateEcoProductDashboardCommand;
use Modules\Ecommerce\EcoProduct\Repositories\EcoProductRepository;

class UpdateEcoProductDashboardHandler
{
    public function __construct(
        private EcoProductRepository $repository,
    ) {
    }

    public function handle(UpdateEcoProductDashboardCommand $updateEcoProductCommand)
    {
        // FIX: Pass the entire command object to the repository method
        $this->repository->updateEcoProduct($updateEcoProductCommand->getId(),$updateEcoProductCommand);
    }
}
