<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Handlers;

use Modules\Project\ProjectType\Commands\UpdateEmployeeContractSettingCommand;
use Modules\Project\ProjectType\Models\EmployeeContractSetting;
use Modules\Project\ProjectType\Services\EmployeeContractSettingService;

class UpdateEmployeeContractSettingHandler
{
    public function __construct(
        private readonly EmployeeContractSettingService $service
    ) {
    }

    public function handle(UpdateEmployeeContractSettingCommand $command): EmployeeContractSetting
    {
        return $this->service->update($command->projectTypeId, $command->dto);
    }
}
