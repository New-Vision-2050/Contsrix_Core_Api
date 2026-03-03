<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Handlers;

use Modules\Project\ProjectType\Commands\UpdateDepartmentContractSettingCommand;
use Modules\Project\ProjectType\Models\DepartmentContractSetting;
use Modules\Project\ProjectType\Services\DepartmentContractSettingService;

class UpdateDepartmentContractSettingHandler
{
    public function __construct(
        private readonly DepartmentContractSettingService $service
    ) {
    }

    public function handle(UpdateDepartmentContractSettingCommand $command): DepartmentContractSetting
    {
        return $this->service->update($command->projectTypeId, $command->dto);
    }
}
