<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Handlers;

use Modules\Project\ProjectType\Commands\UpdateSafetyTaskSettingCommand;
use Modules\Project\ProjectType\Models\SafetyTaskSetting;
use Modules\Project\ProjectType\Services\SafetyTaskSettingService;

class UpdateSafetyTaskSettingHandler
{
    public function __construct(private readonly SafetyTaskSettingService $service) {}
    public function handle(UpdateSafetyTaskSettingCommand $command): SafetyTaskSetting
    { return $this->service->update($command->projectTypeId, $command->dto); }
}