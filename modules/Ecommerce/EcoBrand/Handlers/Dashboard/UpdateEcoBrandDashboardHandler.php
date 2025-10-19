<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBrand\Handlers\Dashboard;

use Modules\Ecommerce\EcoBrand\Commands\Dashboard\UpdateEcoBrandDashboardCommand;
use Modules\Ecommerce\EcoBrand\Repositories\EcoBrandRepository;

class UpdateEcoBrandDashboardHandler
{
    public function __construct(
        private EcoBrandRepository $repository,
    ) {
    }

    public function handle(UpdateEcoBrandDashboardCommand $updateEcoBrandCommand, $file = null)
    {
        $this->repository->updateEcoBrand($updateEcoBrandCommand->getId(), $updateEcoBrandCommand->toArray(), $file);
    }
}
