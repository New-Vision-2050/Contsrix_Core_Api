<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Commands;

use Modules\Project\ProjectType\DTO\UpdateProjectManagementSettingDTO;

class UpdateProjectManagementSettingCommand
{
    public function __construct(public readonly int $projectTypeId, public readonly UpdateProjectManagementSettingDTO $dto) {}
}