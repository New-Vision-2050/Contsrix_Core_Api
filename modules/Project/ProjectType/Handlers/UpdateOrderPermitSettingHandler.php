<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Handlers;

use Modules\Project\ProjectType\Commands\UpdateOrderPermitSettingCommand;
use Modules\Project\ProjectType\Models\OrderPermitSetting;
use Modules\Project\ProjectType\Services\OrderPermitSettingService;

class UpdateOrderPermitSettingHandler
{
    public function __construct(
        private readonly OrderPermitSettingService $service
    ) {
    }

    public function handle(UpdateOrderPermitSettingCommand $command): OrderPermitSetting
    {
        return $this->service->update($command->projectTypeId, $command->dto);
    }
}
