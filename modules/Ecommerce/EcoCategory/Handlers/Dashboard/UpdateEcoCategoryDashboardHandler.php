<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Handlers\Dashboard;

use Modules\Ecommerce\EcoCategory\Commands\Dashboard\UpdateEcoCategoryDashboardCommand;
use Modules\Ecommerce\EcoCategory\Repositories\EcoCategoryRepository;

class UpdateEcoCategoryDashboardHandler
{
    public function __construct(
        private EcoCategoryRepository $repository,
    ) {
    }

    public function handle(UpdateEcoCategoryDashboardCommand $updateEcoCategoryCommand, $file = null)
    {
        $this->repository->updateEcoCategory($updateEcoCategoryCommand->getId(), $updateEcoCategoryCommand->toArray(), $file);
    }
}
