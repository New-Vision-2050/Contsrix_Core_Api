<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Handlers\Dashboard;

use Modules\Ecommerce\EcoAppSetting\Commands\Dashboard\UpdateEcoAppSettingDashboardCommand;
use Modules\Ecommerce\EcoAppSetting\Repositories\EcoAppSettingRepository;

class UpdateEcoAppSettingDashboardHandler
{
    public function __construct(
        private EcoAppSettingRepository $repository,
    ) {
    }

    public function handle(UpdateEcoAppSettingDashboardCommand $updateEcoAppSettingCommand): void
    {
        $this->repository->updateEcoAppSetting($updateEcoAppSettingCommand->getId(), $updateEcoAppSettingCommand->toArray());
    }
}
