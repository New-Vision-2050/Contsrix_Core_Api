<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBusinessActivity\Handlers\Dashboard;

use Modules\Ecommerce\EcoBusinessActivity\Commands\Dashboard\UpdateEcoBusinessActivityDashboardCommand;
use Modules\Ecommerce\EcoBusinessActivity\Repositories\EcoBusinessActivityRepository;

class UpdateEcoBusinessActivityDashboardHandler
{
    public function __construct(
        private EcoBusinessActivityRepository $repository,
    ) {
    }

    public function handle(UpdateEcoBusinessActivityDashboardCommand $updateEcoBusinessActivityCommand)
    {
        $this->repository->updateEcoBusinessActivity($updateEcoBusinessActivityCommand->getId(), $updateEcoBusinessActivityCommand->toArray());
    }
}
