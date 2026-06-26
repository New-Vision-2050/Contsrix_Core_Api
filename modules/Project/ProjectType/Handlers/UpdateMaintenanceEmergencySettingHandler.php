<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Handlers;

use Modules\Project\ProjectType\Commands\UpdateMaintenanceEmergencySettingCommand;
use Modules\Project\ProjectType\Models\MaintenanceEmergencySetting;
use Modules\Project\ProjectType\Services\MaintenanceEmergencySettingService;

class UpdateMaintenanceEmergencySettingHandler
{
    public function __construct(
        private readonly MaintenanceEmergencySettingService $service
    ) {
    }

    public function handle(UpdateMaintenanceEmergencySettingCommand $command): MaintenanceEmergencySetting
    {
        return $this->service->update($command->projectTypeId, $command->dto);
    }
}
