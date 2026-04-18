<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Handlers;

use Modules\Project\ProjectType\Commands\UpdateRolesAndPermissionsSettingCommand;
use Modules\Project\ProjectType\Models\RolesAndPermissionsSetting;
use Modules\Project\ProjectType\Services\RolesAndPermissionsSettingService;

class UpdateRolesAndPermissionsSettingHandler
{
    public function __construct(
        private readonly RolesAndPermissionsSettingService $service
    ) {
    }

    public function handle(UpdateRolesAndPermissionsSettingCommand $command): RolesAndPermissionsSetting
    {
        return $this->service->update($command->projectTypeId, $command->dto);
    }
}
