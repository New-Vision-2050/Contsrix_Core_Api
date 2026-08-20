<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Handlers;

use Modules\Project\ProjectType\Commands\UpdateConstructionSettingCommand;
use Modules\Project\ProjectType\Models\ConstructionSetting;
use Modules\Project\ProjectType\Services\ConstructionSettingService;

class UpdateConstructionSettingHandler
{
    public function __construct(private readonly ConstructionSettingService $service) {}
    public function handle(UpdateConstructionSettingCommand $command): ConstructionSetting
    { return $this->service->update($command->projectTypeId, $command->dto); }
}