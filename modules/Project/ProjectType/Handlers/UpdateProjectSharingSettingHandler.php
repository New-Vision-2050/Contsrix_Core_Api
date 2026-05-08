<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Handlers;

use Modules\Project\ProjectType\Commands\UpdateProjectSharingSettingCommand;
use Modules\Project\ProjectType\Models\ProjectSharingSetting;
use Modules\Project\ProjectType\Services\ProjectSharingSettingService;

class UpdateProjectSharingSettingHandler
{
    public function __construct(
        private readonly ProjectSharingSettingService $service
    ) {
    }

    public function handle(UpdateProjectSharingSettingCommand $command): ProjectSharingSetting
    {
        return $this->service->update($command->projectTypeId, $command->dto);
    }
}
