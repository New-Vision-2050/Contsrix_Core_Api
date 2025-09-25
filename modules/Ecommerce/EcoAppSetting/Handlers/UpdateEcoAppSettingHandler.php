<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Handlers;

use Modules\Ecommerce\EcoAppSetting\Commands\UpdateEcoAppSettingCommand;
use Modules\Ecommerce\EcoAppSetting\Repositories\EcoAppSettingRepository;

class UpdateEcoAppSettingHandler
{
    public function __construct(
        private EcoAppSettingRepository $repository,
    ) {
    }

    public function handle(UpdateEcoAppSettingCommand $updateEcoAppSettingCommand)
    {
        $this->repository->updateEcoAppSetting($updateEcoAppSettingCommand->getId(), $updateEcoAppSettingCommand->toArray());
    }
}
