<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Handlers;

use Modules\Project\ProjectType\Commands\UpdateProjectOrderPermitSettingCommand;
use Modules\Project\ProjectType\Models\ProjectOrderPermitSetting;
use Modules\Project\ProjectType\Services\ProjectOrderPermitSettingService;

class UpdateProjectOrderPermitSettingHandler
{
    public function __construct(private readonly ProjectOrderPermitSettingService $service) {}
    public function handle(UpdateProjectOrderPermitSettingCommand $command): ProjectOrderPermitSetting
    { return $this->service->update($command->projectTypeId, $command->dto); }
}