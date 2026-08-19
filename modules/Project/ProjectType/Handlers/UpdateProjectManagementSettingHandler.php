<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Handlers;

use Modules\Project\ProjectType\Commands\UpdateProjectManagementSettingCommand;
use Modules\Project\ProjectType\Models\ProjectManagementSetting;
use Modules\Project\ProjectType\Services\ProjectManagementSettingService;

class UpdateProjectManagementSettingHandler
{
    public function __construct(private readonly ProjectManagementSettingService $service) {}
    public function handle(UpdateProjectManagementSettingCommand $command): ProjectManagementSetting
    { return $this->service->update($command->projectTypeId, $command->dto); }
}