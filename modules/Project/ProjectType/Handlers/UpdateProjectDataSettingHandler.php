<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Handlers;

use Modules\Project\ProjectType\Commands\UpdateProjectDataSettingCommand;
use Modules\Project\ProjectType\Models\ProjectDataSetting;
use Modules\Project\ProjectType\Services\ProjectDataSettingService;

class UpdateProjectDataSettingHandler
{
    public function __construct(
        private readonly ProjectDataSettingService $service
    ) {
    }

    public function handle(UpdateProjectDataSettingCommand $command): ProjectDataSetting
    {
        return $this->service->update($command->projectTypeId, $command->dto);
    }
}
